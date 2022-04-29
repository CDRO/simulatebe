<?php

namespace Cabag\Simulatebe\Middleware;

use Cabag\Simulatebe\Configuration\Configuration;
use GeorgRinger\News\Domain\Repository\LinkRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class BackendUserSimulator implements MiddlewareInterface
{
    /**
     * @var TypoScriptFrontendController
     */
    private $typoScriptFrontend;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(TypoScriptFrontendController $typoScriptFrontend = null, Configuration $configuration = null)
    {
        $this->typoScriptFrontend = $typoScriptFrontend ?? $GLOBALS['TSFE'];
        $this->configuration = $configuration ?? Configuration::fromGlobals();
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // if no user is logged in than we cannot authenticate any backend user
        if (!$this->isFrontendUserLoggedIn()) {
            return $handler->handle($request);
        }

        // the login can be activated using a query parameter
        // if that parameter is not given then, no backend user should be simulated
        $hasLinkParameterInRequest = (bool)$request->getQueryParams()[$this->configuration->getLinkParameterName()];
        if ($this->configuration->getOnLinkParameter() && !$hasLinkParameterInRequest) {
            return $handler->handle($request);
        }


        $tempBackendUserAuthentication = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $backendCookieName = $tempBackendUserAuthentication->name;
        $simulateBeCookieName = $this->configuration->getCookieName();

        $cookieParams = $request->getCookieParams();

        // Check if the BE user is already logged in.
        // In that case, no more action is needed in this middleware,
        // because the BackendUserAuthenticator middleware will take over and will login the backend user
        if ($cookieParams[$backendCookieName]) {
            // check if frontend user will be logged off
            // if the backend user is simulated
            // then the backend user will be logged off as well
            if (
                $request->getQueryParams()['logintype'] === 'logout'
                && $cookieParams[$backendCookieName] === $cookieParams[$simulateBeCookieName]
            ) {
                $response = $handler->handle($request);

                if (!$GLOBALS['BE_USER'] instanceof BackendUserAuthentication) {
                    $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
                    $GLOBALS['BE_USER']->start();
                }

                $GLOBALS['BE_USER']->logoff();

                $response = new RedirectResponse(
                    $request->getUri(),
                    307,
                    $response->getHeaders()
                );
                $response = $this->withSessionCookie($response,$this->configuration->getCookieName(),'');
                return $response;
            }

            return $handler->handle($request);
        }

        // if backend user is already simulated
        // then do not try to simulate again
        // otherwise the user cannot log off the backend anymore
        if ($cookieParams[$this->configuration->getCookieName()]) {
            return $handler->handle($request);
        }

        // at this point we know
        // 1. user is logged in as frontend user
        // 2. user is not logged in as backend user yet

        $backendUser = $this->getSimulatedBackendUser();

        if ($backendUser === null) {
            return $handler->handle($request);
        }

        // create session for backend user
        // the backend user will then be initiated by the BackendAuthenticator middleware
        $backendUserSessionId = $tempBackendUserAuthentication->id = $this->typoScriptFrontend->fe_user->id;
        $sessionRecord = $tempBackendUserAuthentication->createUserSession($backendUser);

        if ($this->configuration->getFakeTimeout() > 0) {
            $sessionRecord['ses_tstamp'] += $this->configuration->getFakeTimeout();
            $sessionBackend = GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend($tempBackendUserAuthentication->loginType);
            $sessionBackend->set($backendUserSessionId, $sessionRecord);
        }

        $cookieParams[$backendCookieName] = $_COOKIE[$backendCookieName] = $tempBackendUserAuthentication->id;
        $request = $request->withCookieParams($cookieParams);
        $response = $handler->handle($request);

        $response = $this->withSessionCookie(
            $response,
            $this->configuration->getCookieName(),
            $backendUserSessionId
        );

        $response = $this->withSessionCookie(
            $response,
            $backendCookieName,
            $backendUserSessionId
        );

        // if on link parameter is active, then redirect to /typo3 backend on first request
        if ($this->configuration->getOnLinkParameter() && $hasLinkParameterInRequest) {
            return new RedirectResponse(
                new Uri('/typo3'),
                307,
                $response->getHeaders()
            );
        }

        return $response;
    }

    /**
     * Returns the backend user that will be simulated.
     *
     * Returns null if the logged in frontend user is not entitled to simulate a backend user.
     *
     * @return array|null
     */
    private function getSimulatedBackendUser(): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder
            ->select('*')
            ->from('be_users')
            // backend users MUST be stored on the root level of the application
            ->where('pid = 0')
            ->setMaxResults(1);

        $simulateBeUserUid = (int)$this->typoScriptFrontend->fe_user->user['tx_simulatebe_beuser'];
        if ($simulateBeUserUid > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($simulateBeUserUid))
            );
        } else {
            $username = $this->typoScriptFrontend->fe_user->user['username'];
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('tx_simulatebe_feuserusername', $queryBuilder->createNamedParameter($username))
            );
        }

        return $queryBuilder->execute()->fetch() ?: null;
    }

    /**
     * Checks if the user is logged into the frontend.
     *
     * @return bool
     */
    private function isFrontendUserLoggedIn(): bool
    {
        return $this->typoScriptFrontend->fe_user instanceof FrontendUserAuthentication
            && $this->typoScriptFrontend->fe_user->user !== null;
    }

    /**
     * @param ResponseInterface $response
     * @param string $name
     * @param string $value
     * @param string $loginType
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Exception
     */
    private function withSessionCookie(ResponseInterface $response, string $name, string $value, $loginType = ''): ResponseInterface
    {
        $domain = $this->getCookieDomain($loginType);
        $path = ($domain ? '/' : GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
        $secure = (bool)$GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieSecure'] && GeneralUtility::getIndpEnv('TYPO3_SSL');
        $httponly = (bool)$GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieHttpOnly'];

        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieSecure'] && !GeneralUtility::getIndpEnv('TYPO3_SSL')) {
            throw new \TYPO3\CMS\Core\Exception(
                'Cookie was not set since HTTPS was forced in $TYPO3_CONF_VARS[SYS][cookieSecure].',
                1254325546
            );
        }

        $headerValue = $name.'='.$value;
        $headerValue .= '; Path='.$path;
        $headerValue .= $secure ? '; Secure' : '';
        $headerValue .= $httponly ? '; HttpOnly' : '';
        $headerValue .= '; Domain='.$domain;

        return $response->withAddedHeader('Set-Cookie', $headerValue);
    }

    /**
     * Gets the domain to be used on setting cookies.
     * The information is taken from the value in $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'].
     *
     * @param string $loginType
     * @return string The domain to be used on setting cookies
     * @see AbstractUserAuthentication::getCookieDomain()
     *
     */
    private function getCookieDomain(string $loginType): string
    {
        $result = '';
        $cookieDomain = $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'];
        // If a specific cookie domain is defined for a given TYPO3_MODE,
        // use that domain
        if (!empty($GLOBALS['TYPO3_CONF_VARS'][$loginType]['cookieDomain'])) {
            $cookieDomain = $GLOBALS['TYPO3_CONF_VARS'][$loginType]['cookieDomain'];
        }
        if ($cookieDomain) {
            if ($cookieDomain[0] === '/') {
                $match = [];
                $matchCnt = @preg_match($cookieDomain, GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'), $match);
                if ($matchCnt === false) {
                    $this->logger->critical('The regular expression for the cookie domain ('.$cookieDomain.') contains errors. The session is not shared across sub-domains.');
                } elseif ($matchCnt) {
                    $result = $match[0];
                }
            } else {
                $result = $cookieDomain;
            }
        }
        return $result;
    }
}
