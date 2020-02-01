<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management\Client;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

class Addon
{
    /**
     * @var string
     */
    protected $aws;

    /**
     * @var string
     */
    protected $azure_blob;

    /**
     * @var string
     */
    protected $azure_sb;

    /**
     * @var string
     */
    protected $rms;

    /**
     * @var string
     */
    protected $mscrm;

    /**
     * @var string
     */
    protected $slack;

    /**
     * @var string
     */
    protected $sentry;

    /**
     * @var string
     */
    protected $box;

    /**
     * @var string
     */
    protected $cloudbees;

    /**
     * @var string
     */
    protected $concur;

    /**
     * @var string
     */
    protected $dropbox;

    /**
     * @var string
     */
    protected $echosign;

    /**
     * @var string
     */
    protected $egnyte;

    /**
     * @var string
     */
    protected $firebase;

    /**
     * @var string
     */
    protected $newrelic;

    /**
     * @var string
     */
    protected $office365;

    /**
     * @var string
     */
    protected $salesforce;

    /**
     * @var string
     */
    protected $salesforce_api;

    /**
     * @var string
     */
    protected $salesforce_sandbox_api;

    /**
     * @var string
     */
    protected $samlp;

    /**
     * @var string
     */
    protected $layer;

    /**
     * @var string
     */
    protected $sap_api;

    /**
     * @var string
     */
    protected $sharepoint;

    /**
     * @var string
     */
    protected $springcm;

    /**
     * @var string
     */
    protected $wams;

    /**
     * @var string
     */
    protected $wsfed;

    /**
     * @var string
     */
    protected $zendesk;

    /**
     * @var string
     */
    protected $zoom;

    /**
     * @return string
     */
    public function getAws()
    {
        return $this->aws;
    }

    public function setAws(string $aws): void
    {
        $this->aws = $aws;
    }

    /**
     * @return string
     */
    public function getAzureBlob()
    {
        return $this->azure_blob;
    }

    public function setAzureBlob(string $azure_blob): void
    {
        $this->azure_blob = $azure_blob;
    }

    /**
     * @return string
     */
    public function getAzureSb()
    {
        return $this->azure_sb;
    }

    public function setAzureSb(string $azure_sb): void
    {
        $this->azure_sb = $azure_sb;
    }

    /**
     * @return string
     */
    public function getRms()
    {
        return $this->rms;
    }

    public function setRms(string $rms): void
    {
        $this->rms = $rms;
    }

    /**
     * @return string
     */
    public function getMscrm()
    {
        return $this->mscrm;
    }

    public function setMscrm(string $mscrm): void
    {
        $this->mscrm = $mscrm;
    }

    /**
     * @return string
     */
    public function getSlack()
    {
        return $this->slack;
    }

    public function setSlack(string $slack): void
    {
        $this->slack = $slack;
    }

    /**
     * @return string
     */
    public function getSentry()
    {
        return $this->sentry;
    }

    public function setSentry(string $sentry): void
    {
        $this->sentry = $sentry;
    }

    /**
     * @return string
     */
    public function getBox()
    {
        return $this->box;
    }

    public function setBox(string $box): void
    {
        $this->box = $box;
    }

    /**
     * @return string
     */
    public function getCloudbees()
    {
        return $this->cloudbees;
    }

    public function setCloudbees(string $cloudbees): void
    {
        $this->cloudbees = $cloudbees;
    }

    /**
     * @return string
     */
    public function getConcur()
    {
        return $this->concur;
    }

    public function setConcur(string $concur): void
    {
        $this->concur = $concur;
    }

    /**
     * @return string
     */
    public function getDropbox()
    {
        return $this->dropbox;
    }

    public function setDropbox(string $dropbox): void
    {
        $this->dropbox = $dropbox;
    }

    /**
     * @return string
     */
    public function getEchosign()
    {
        return $this->echosign;
    }

    public function setEchosign(string $echosign): void
    {
        $this->echosign = $echosign;
    }

    /**
     * @return string
     */
    public function getEgnyte()
    {
        return $this->egnyte;
    }

    public function setEgnyte(string $egnyte): void
    {
        $this->egnyte = $egnyte;
    }

    /**
     * @return string
     */
    public function getFirebase()
    {
        return $this->firebase;
    }

    public function setFirebase(string $firebase): void
    {
        $this->firebase = $firebase;
    }

    /**
     * @return string
     */
    public function getNewrelic()
    {
        return $this->newrelic;
    }

    public function setNewrelic(string $newrelic): void
    {
        $this->newrelic = $newrelic;
    }

    /**
     * @return string
     */
    public function getOffice365()
    {
        return $this->office365;
    }

    public function setOffice365(string $office365): void
    {
        $this->office365 = $office365;
    }

    /**
     * @return string
     */
    public function getSalesforce()
    {
        return $this->salesforce;
    }

    public function setSalesforce(string $salesforce): void
    {
        $this->salesforce = $salesforce;
    }

    /**
     * @return string
     */
    public function getSalesforceApi()
    {
        return $this->salesforce_api;
    }

    public function setSalesforceApi(string $salesforce_api): void
    {
        $this->salesforce_api = $salesforce_api;
    }

    /**
     * @return string
     */
    public function getSalesforceSandboxApi()
    {
        return $this->salesforce_sandbox_api;
    }

    public function setSalesforceSandboxApi(string $salesforce_sandbox_api): void
    {
        $this->salesforce_sandbox_api = $salesforce_sandbox_api;
    }

    /**
     * @return string
     */
    public function getSamlp()
    {
        return $this->samlp;
    }

    public function setSamlp(string $samlp): void
    {
        $this->samlp = $samlp;
    }

    /**
     * @return string
     */
    public function getLayer()
    {
        return $this->layer;
    }

    public function setLayer(string $layer): void
    {
        $this->layer = $layer;
    }

    /**
     * @return string
     */
    public function getSapApi()
    {
        return $this->sap_api;
    }

    public function setSapApi(string $sap_api): void
    {
        $this->sap_api = $sap_api;
    }

    /**
     * @return string
     */
    public function getSharepoint()
    {
        return $this->sharepoint;
    }

    public function setSharepoint(string $sharepoint): void
    {
        $this->sharepoint = $sharepoint;
    }

    /**
     * @return string
     */
    public function getSpringcm()
    {
        return $this->springcm;
    }

    public function setSpringcm(string $springcm): void
    {
        $this->springcm = $springcm;
    }

    /**
     * @return string
     */
    public function getWams()
    {
        return $this->wams;
    }

    public function setWams(string $wams): void
    {
        $this->wams = $wams;
    }

    /**
     * @return string
     */
    public function getWsfed()
    {
        return $this->wsfed;
    }

    public function setWsfed(string $wsfed): void
    {
        $this->wsfed = $wsfed;
    }

    /**
     * @return string
     */
    public function getZendesk()
    {
        return $this->zendesk;
    }

    public function setZendesk(string $zendesk): void
    {
        $this->zendesk = $zendesk;
    }

    /**
     * @return string
     */
    public function getZoom()
    {
        return $this->zoom;
    }

    public function setZoom(string $zoom): void
    {
        $this->zoom = $zoom;
    }
}
