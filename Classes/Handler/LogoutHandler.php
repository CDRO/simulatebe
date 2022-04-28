<?php
namespace Cabag\Simulatebe\Handler;

use Cabag\Simulatebe\Configuration\Configuration;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * We cannot handle logout in BackendUserSimulator because it is called
 * after FrontendUserAuthenticator, and there is no trace left of the
 * frontend user when BackendUserSimulator is called.
 */
class LogoutHandler
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Called whenever a backend or frontend user logs out
     */
    public function logout(array $params, AbstractUserAuthentication $auth)
    {
        if (!$auth instanceof FrontendUserAuthentication) {
            return;
        }
        if (!$this->context->getPropertyFromAspect('backend.user', 'isLoggedIn')) {
            return;
        }

        if ($auth->id != $GLOBALS['BE_USER']->id) {
            //difference session id between frontend and backend
            //keep the backend session
            return;
        }

        $GLOBALS['BE_USER']->logoff();

        //simulatebe cookie gets removed in BackendUserSimulator
    }
}
