<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant;

class Flag
{
    /**
     * Enables the first version of the Change Password flow. We've deprecated this option and recommending a safer flow.
     * This flag is only for backwards compatibility.
     *
     * @var bool
     */
    protected $changePwdFlowV1;

    /**
     * This flag enables the APIs section.
     *
     * @var bool
     */
    protected $enableApisSection;

    /**
     * If set to true all Impersonation functionality is disabled for the Tenant. This is a read-only attribute.
     *
     * @var bool
     */
    protected $disableImpersonation;

    /**
     * This flag determines whether all current connections shall be enabled when a new client is created. Default value is true.
     *
     * @var bool
     */
    protected $enableClientConnections;

    /**
     * This flag determines whether all current connections shall be enabled when a new client is created. Default value is true.
     *
     * @var bool
     */
    protected $enablePipeline2;

    /**
     * @return bool
     */
    public function isChangePwdFlowV1()
    {
        return $this->changePwdFlowV1;
    }

    public function setChangePwdFlowV1(bool $changePwdFlowV1)
    {
        $this->changePwdFlowV1 = $changePwdFlowV1;
    }

    /**
     * @return bool
     */
    public function isEnableApisSection()
    {
        return $this->enableApisSection;
    }

    public function setEnableApisSection(bool $enableApisSection)
    {
        $this->enableApisSection = $enableApisSection;
    }

    /**
     * @return bool
     */
    public function isDisableImpersonation()
    {
        return $this->disableImpersonation;
    }

    public function setDisableImpersonation(bool $disableImpersonation)
    {
        $this->disableImpersonation = $disableImpersonation;
    }

    /**
     * @return bool
     */
    public function isEnableClientConnections()
    {
        return $this->enableClientConnections;
    }

    public function setEnableClientConnections(bool $enableClientConnections)
    {
        $this->enableClientConnections = $enableClientConnections;
    }

    /**
     * @return bool
     */
    public function isEnablePipeline2()
    {
        return $this->enablePipeline2;
    }

    public function setEnablePipeline2(bool $enablePipeline2)
    {
        $this->enablePipeline2 = $enablePipeline2;
    }
}
