<?php

namespace Cabag\Simulatebe\Configuration;

class Configuration
{
    private $onLinkParameter = false;
    private $linkParameterName = 'login';
    private $fakeTimeout = 0;
    private $cookieName = 'simulatebe';

    public function __construct(bool $onLinkParameter, string $linkParameterName, int $fakeTimeout, string $cookieName)
    {
        $this->onLinkParameter = $onLinkParameter;
        $this->linkParameterName = $linkParameterName;
        $this->fakeTimeout = $fakeTimeout;
        $this->cookieName = $cookieName;
    }

    public static function fromGlobals(): self
    {
        $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('simulatebe');

        return new static(
            (bool)$configuration['simulatebeOnLinkParameter'],
            (string)$configuration['simulatebeLinkParameter'],
            (int)$configuration['simulatebeFakeTimeout'],
            (string)$configuration['simulatebeCookieName']
        );
    }

    /**
     * @return bool
     */
    public function getOnLinkParameter(): bool
    {
        return $this->onLinkParameter;
    }

    /**
     * @return string
     */
    public function getLinkParameterName(): string
    {
        return $this->linkParameterName;
    }

    /**
     * @return int
     */
    public function getFakeTimeout(): int
    {
        return $this->fakeTimeout;
    }

    /**
     * @return string
     */
    public function getCookieName(): string
    {
        return $this->cookieName;
    }
}
