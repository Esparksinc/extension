<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Esparksinc\Extension\Block\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Block\Product\AbstractProduct;
use Esparksinc\Extension\Helper\Data as Helper;

/**
 * Product View block
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class View extends AbstractProduct implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * Magento string lib
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $helper;
    protected $string;
    /**
     * Determine product type
     *
     * @var \Magento\ConfigurableProduct\Model\Product\Type
     */
    protected $getProductsTypes;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     * @deprecated 102.0.0
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $_productHelper;

    /**
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $productTypeConfig;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $_localeFormat;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var itemCollectionInterface
     */
    protected $itemCollection;
    /**
     * @var cacheManager for cache flush
     */
    protected $cacheManager;
    /**
     * @param Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ProductRepositoryInterface|\Magento\Framework\Pricing\PriceCurrencyInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Helper $helper, 
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Reports\Model\ResourceModel\Product\Collection $prodCollection,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ProductFactory $getProductsTypes,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollection,
        \Magento\Reports\Model\ResourceModel\Event\Type\CollectionFactory $eventTypeCollectionFactory,
        \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $prodCollectionFactory,
         \Magento\Framework\App\Cache\Manager $cacheManager,
        array $data = []
    ) {
        $this->_productHelper = $productHelper;
        $this->helper = $helper;
        $this->urlEncoder = $urlEncoder;
        $this->_jsonEncoder = $jsonEncoder;
        $this->productTypeConfig = $productTypeConfig;
        $this->_prodCollection = $prodCollection;
        $this->string = $string;
        $this->getProductsTypes = $getProductsTypes;       
        $this->itemCollection=$itemCollection;
        $this->_stockItemRepository = $stockItemRepository;
        $this->_localeFormat = $localeFormat;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
        $this->priceCurrency = $priceCurrency;
        $this->cacheManager = $cacheManager;
        $this->eventTypeCollectionFactory = $eventTypeCollectionFactory;
        $this->prodCollectionFactory = $prodCollectionFactory;
        parent::__construct(
            $context,
            $data
        );
    }

    // @codingStandardsIgnoreEnd

    /**
     * Return wishlist widget options
     *
     * @return array
     * @deprecated 101.0.1
     */
    public function getWishlistOptions()
    {
        return ['productType' => $this->getProduct()->getTypeId()];
    }
    /**
     * Retrieve current product model
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        if (!$this->_coreRegistry->registry('product') && $this->getProductId()) {
            $product = $this->productRepository->getById($this->getProductId());
            $this->_coreRegistry->register('product', $product);
        }
        return $this->_coreRegistry->registry('product');
    }

    /**
     * Check if product can be emailed to friend
     *
     * @return bool
     */
    public function canEmailToFriend()
    {
        return false;
    }

    /**
     * Retrieve url for direct adding product to cart
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $additional
     * @return string
     */
    public function getAddToCartUrl($product, $additional = [])
    {
        if ($this->hasCustomAddToCartUrl()) {
            return $this->getCustomAddToCartUrl();
        }

        if ($this->getRequest()->getParam('wishlist_next')) {
            $additional['wishlist_next'] = 1;
        }

        $addUrlKey = \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED;
        $addUrlValue = $this->_urlBuilder->getUrl('*/*/*', ['_use_rewrite' => true, '_current' => true]);
        $additional[$addUrlKey] = $this->urlEncoder->encode($addUrlValue);

        return $this->_cartHelper->getAddUrl($product, $additional);
    }

    /**
     * Get JSON encoded configuration array which can be used for JS dynamic
     * price calculation depending on product options
     *
     * @return string
     */
    public function getJsonConfig()
    {
        /* @var $product \Magento\Catalog\Model\Product */
        $product = $this->getProduct();

        if (!$this->hasOptions()) {
            $config = [
                'productId' => $product->getId(),
                'priceFormat' => $this->_localeFormat->getPriceFormat()
            ];
            return $this->_jsonEncoder->encode($config);
        }

        $tierPrices = [];
        $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
        foreach ($tierPricesList as $tierPrice) {
            $tierPrices[] = $tierPrice['price']->getValue();
        }
        $config = [
            'productId'   => $product->getId(),
            'priceFormat' => $this->_localeFormat->getPriceFormat(),
            'prices'      => [
                'oldPrice'   => [
                    'amount'      => $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(),
                    'adjustments' => []
                ],
                'basePrice'  => [
                    'amount'      => $product->getPriceInfo()->getPrice('final_price')->getAmount()->getBaseAmount(),
                    'adjustments' => []
                ],
                'finalPrice' => [
                    'amount'      => $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(),
                    'adjustments' => []
                ]
            ],
            'idSuffix'    => '_clone',
            'tierPrices'  => $tierPrices
        ];

        $responseObject = new \Magento\Framework\DataObject();
        $this->_eventManager->dispatch('catalog_product_view_config', ['response_object' => $responseObject]);
        if (is_array($responseObject->getAdditionalOptions())) {
            foreach ($responseObject->getAdditionalOptions() as $option => $value) {
                $config[$option] = $value;
            }
        }

        return $this->_jsonEncoder->encode($config);
    }

    /**
     * Return true if product has options
     *
     * @return bool
     */
    public function hasOptions()
    {
        if ($this->getProduct()->getTypeInstance()->hasOptions($this->getProduct())) {
            return true;
        }
        return false;
    }

    /**
     * Check if product has required options
     *
     * @return bool
     */
    public function hasRequiredOptions()
    {
        return $this->getProduct()->getTypeInstance()->hasRequiredOptions($this->getProduct());
    }

    /**
     * Define if setting of product options must be shown instantly.
     * Used in case when options are usually hidden and shown only when user
     * presses some button or link. In editing mode we better show these options
     * instantly.
     *
     * @return bool
     */
    public function isStartCustomization()
    {
        return $this->getProduct()->getConfigureMode() || $this->_request->getParam('startcustomization');
    }

    /**
     * Get default qty - either as preconfigured, or as 1.
     * Also restricts it by minimal qty.
     *
     * @param null|\Magento\Catalog\Model\Product $product
     * @return int|float
     */
    public function getProductDefaultQty($product = null)
    {
        if (!$product) {
            $product = $this->getProduct();
        }

        $qty = $this->getMinimalQty($product);
        $config = $product->getPreconfiguredValues();
        $configQty = $config->getQty();
        if ($configQty > $qty) {
            $qty = $configQty;
        }

        return $qty;
    }

    /**
     * Get container name, where product options should be displayed
     *
     * @return string
     */
    public function getOptionsContainer()
    {
        return $this->getProduct()->getOptionsContainer() == 'container1' ? 'container1' : 'container2';
    }

    /**
     * Check whether quantity field should be rendered
     *
     * @return bool
     */
    public function shouldRenderQuantity()
    {
        return !$this->productTypeConfig->isProductSet($this->getProduct()->getTypeId());
    }

    /**
     * Get Validation Rules for Quantity field
     *
     * @return array
     */
    public function getQuantityValidators()
    {
        $validators = [];
        $validators['required-number'] = true;
        return $validators;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = $this->getProduct()->getIdentities();
        $category = $this->_coreRegistry->registry('current_category');
        if ($category) {
            $identities[] = Category::CACHE_TAG . '_' . $category->getId();
        }
        return $identities;
    }

    /**
     * Retrieve customer data object
     *
     * @return int
     */
    protected function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * Retrieve how many times product view
     *
     * @return int
     */
    public function viewCount($id)
    {

    $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
    $prodData = $this->_prodCollection->setProductAttributeSetId($id)->addViewsCount()->getData();


    if (count($prodData) > 0) {
        foreach ($prodData as $product) {
            if ($product['entity_id'] == $id) {
                return (int) $product['views'];
            }
        }
    }

    return 0;
    }

    /**
     * Retrieve how many times product Order
     *
     * @return int
     */
    public function getProductOrder($id)
    {
        $itemCollection = $this->itemCollection->create()
                       ->addFieldToFilter('product_id', $id);
        return count($itemCollection); // this will give number of time ordered
    }

     /**
      * Retrieve Product Stock
      *
      * @return int
      */
    public function getStockItem($productId)
    {
        return $this->_stockItemRepository->get($productId);
    }

    /**
     * Return Product Type
     *
     * @return 1 if product is configurable
     */
    public function getProductType($productId)
    {
        $getProductsTypes=$this->getProductsTypes->create()->load($productId);
        $productType = $getProductsTypes->getTypeID();
        
        if ($productType == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                return 1;
        }
    }
    public function getHelper()
    {
        return $this->helper;
    }
}
