<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\CheckoutAgreements\Model;

use Magento\CheckoutAgreements\Model\Resource\Agreement\CollectionFactory as AgreementCollectionFactory;
use Magento\CheckoutAgreements\Model\Resource\Agreement\Collection as AgreementCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\CheckoutAgreements\Api\CheckoutAgreementsRepositoryInterface;
use Magento\CheckoutAgreements\Model\Resource\Agreement as AgreementResource;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Store\Model\Store;

/**
 * Checkout agreement repository.
 */
class CheckoutAgreementsRepository implements CheckoutAgreementsRepositoryInterface
{
    /**
     * Collection factory.
     *
     * @var AgreementCollectionFactory
     */
    private $collectionFactory;

    /**
     * Store manager.
     *
     * @var  \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Scope config.
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AgreementResource
     */
    private $resourceModel;

    /**
     * @var AgreementFactory
     */
    private $agreementFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * Constructs a checkout agreement data object.
     *
     * @param AgreementCollectionFactory $collectionFactory Collection factory.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Store manager.
     * @param ScopeConfigInterface $scopeConfig Scope config.
     * @param AgreementResource $agreementResource
     * @param AgreementFactory $agreementFactory
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        AgreementCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        AgreementResource $agreementResource,
        AgreementFactory $agreementFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->resourceModel = $agreementResource;
        $this->agreementFactory = $agreementFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\CheckoutAgreements\Api\Data\AgreementInterface[] Array of checkout agreement data objects.
     */
    public function getList()
    {
        if (!$this->scopeConfig->isSetFlag('checkout/options/enable_agreements', ScopeInterface::SCOPE_STORE)) {
            return [];
        }
        $storeId = $this->storeManager->getStore()->getId();
        /** @var $agreementCollection AgreementCollection */
        $agreementCollection = $this->collectionFactory->create();
        $agreementCollection->addStoreFilter($storeId);
        $agreementCollection->addFieldToFilter('is_active', 1);

        $agreementDataObjects = [];
        foreach ($agreementCollection as $agreement) {
            $agreementDataObjects[] = $agreement;
        }

        return $agreementDataObjects;
    }

    /**
     * {@inheritdoc}
     */
    public function save(\Magento\CheckoutAgreements\Api\Data\AgreementInterface $data, $storeId = null)
    {
        $id = $data->getAgreementId();

        if ($id) {
            $data = $this->get($id, $storeId)->addData($data->getData());
        }
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        $data->setStores($storeId);
        try {
            $this->resourceModel->save($data);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Unable to save checkout agreement %1', $data->getAgreementId())
            );
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(\Magento\CheckoutAgreements\Api\Data\AgreementInterface $data)
    {
        try {
            $this->resourceModel->delete($data);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotDeleteException(
                __('Unable to remove checkout agreement %1', $data->getAgreementId())
            );
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        $model = $this->get($id);
        $this->delete($model);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, $storeId = null)
    {
        /** @var AgreementFactory $agreement */
        $agreement = $this->agreementFactory->create();
        $this->resourceModel->load($agreement, $id);
        if (!$agreement->getId()) {
            throw new NoSuchEntityException(__('Checkout agreement with specified ID "%1" not found.', $id));
        }
        return $agreement;
    }
}
