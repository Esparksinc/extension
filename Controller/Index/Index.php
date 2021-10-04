<?php
namespace Esparksinc\Extension\Controller\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlFactory;

class Index extends \Magento\Framework\App\Action\Action
{
        /**
         * @var \Magento\Framework\View\Result\PageFactory
         */
    protected $resultPageFactory;

    private $blockname;
        /**
         * @var \Magento\Framework\UrlFactory
         */
    protected $urlFactory;
       
        /**
         * @var \Magento\Framework\Controller\Result\JsonFactory
         */
    protected $resultJsonFactory;

    protected $resultLayoutFactory;

    protected $stockStateInterface;
       
        /**
         * @param \Magento\Framework\App\Action\Context $context
         * @param \Magento\Framework\View\Result\PageFactory resultPageFactory
         * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
         */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $product,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Esparksinc\Extension\Block\Product\View $blockname,
        UrlFactory $urlFactory
    ) {
        $this->product =$product;
        $this->resultPageFactory    = $resultPageFactory;
        $this->resultJsonFactory    = $resultJsonFactory;
        $this->resultLayoutFactory  = $resultLayoutFactory;
        $this->blockname = $blockname;
        $this->urlModel             = $urlFactory->create();
        parent::__construct($context);
    }
    /**
     * Example for returning JSON data
     *
     * @return string
     */
    public function execute()
    {
    
        $selectedProductID = $this->getRequest()->getPost('SelectedProductID');
        $result = $this->resultJsonFactory->create();

        $product=$this->product->create()->load($selectedProductID);
        $product_qty = $product['quantity_and_stock_status']['qty'];
       
        $countOrder=$this->blockname->getProductOrder($selectedProductID);
    
        $data = [
            'stock' =>$product_qty,
            'order'=>$countOrder,
            'success' => true
        ];
 
        $result->setData($data);
 
        return $result;

        }
}
