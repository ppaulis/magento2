<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Controller\Adminhtml\Design\Config;

use Magento\Backend\App\Action;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Theme\Model\DesignConfigRepository;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Theme\Model\Data\Design\ConfigFactory;

class Save extends Action
{
    /**
     * @var DesignConfigRepository
     */
    protected $designConfigRepository;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param Context $context
     * @param DesignConfigRepository $designConfigRepository
     * @param ConfigFactory $configFactory
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        DesignConfigRepository $designConfigRepository,
        ConfigFactory $configFactory,
        DataPersistorInterface $dataPersistor
    ) {
        $this->designConfigRepository = $designConfigRepository;
        $this->configFactory = $configFactory;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }

    /**
     * Check the permission to manage themes
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Config::config_design');
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $scope = $this->getRequest()->getParam('scope');
        $scopeId = (int)$this->getRequest()->getParam('scope_id');
        $data = $this->getRequestData();

        try {
            $designConfigData = $this->configFactory->create($scope, $scopeId, $data);
            $this->designConfigRepository->save($designConfigData);
            $this->messageManager->addSuccess(__('You saved the configuration.'));

            $this->dataPersistor->clear('theme_design_config');

            $resultRedirect->setPath('theme/design_config/');
            return $resultRedirect;
        } catch (LocalizedException $e) {
            $messages = explode("\n", $e->getMessage());
            foreach ($messages as $message) {
                $this->messageManager->addError(__('%1', $message));
            }
        } catch (\Exception $e) {
            $this->messageManager->addException(
                $e,
                __('Something went wrong while saving this configuration:') . ' ' . $e->getMessage()
            );
        }

        $this->dataPersistor->set('theme_design_config', $data);

        $resultRedirect->setPath('theme/design_config/edit', ['scope' => $scope, 'scope_id' => $scopeId]);
        return $resultRedirect;
    }

    /**
     * Extract data from request
     *
     * @return array
     */
    protected function getRequestData()
    {
        $data = array_merge(
            $this->getRequest()->getParams(),
            $this->getRequest()->getFiles()->toArray()
        );
        $data = array_filter($data, function ($param) {
            return isset($param['error']) && $param['error'] > 0 ? false : true;
        });
        return $data;
    }
}
