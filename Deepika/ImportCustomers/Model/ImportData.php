<?php 

namespace Deepika\ImportCustomers\Model;


use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;


class ImportData{

	protected $customerFactory;
    protected $state;
    protected $storeManager;
    protected $customerRepository;
    protected $encryptor;
    protected $filesystem;


	public function __construct(
        StoreManagerInterface $storeManager,
        CustomerInterfaceFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        State $state,
        EncryptorInterface $encryptor,
        Filesystem $filesystem
	    ) {
	        $this->storeManager     = $storeManager;
	        $this->customerFactory  = $customerFactory;
	        $this->customerRepository = $customerRepository;
	        $this->state = $state;
	        $this->encryptor = $encryptor;
	        $this->filesystem = $filesystem;
	    }

	/**
     * Import Customer Data By CSV
     *
     * @throws LocalizedException
     * @return void
     */
    public function importByCsv($filePath){

    	$this->state->setAreaCode(Area::AREA_GLOBAL);

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $output->writeln("<error>Unable to open file: $filePath</error>");
            return;
        }

        $header = fgetcsv($handle, 1000, ',');

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            try {
                if (count($data) < 3) {
                    $output->writeln("<error>Invalid data for customer: " . implode(',', $data) . "</error>");
                    continue;
                }

                $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();

                $customer   = $this->customerFactory->create();
                $customer->setWebsiteId($websiteId);
                $customer->setFirstname($data[0]);
                $customer->setLastname($data[1]);
                $hashedPassword = $this->encryptor->getHash('Password@321', true);
                $customer->setEmail($data[2]);
                $customer->setGroupId(1);

                //$customer->save();
               $this->customerRepository->save($customer, $hashedPassword);
                $output->writeln("<info>Imported: {$data[0]}</info>");
            } catch (LocalizedException $e) {
                $output->writeln("<error>Error importing customer: {$data[0]} - {$e->getMessage()}</error>");
            }
        }

        fclose($handle);
        $output->writeln("<info>Customer import completed.</info>");
    }

    /**
     * Import Customer Data By CSV
     *
     * @throws LocalizedException
     * @return void
     */
    public function importByJson($filename){

    	$jsonData = file_get_contents($filename);
        $customers = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LocalizedException(__('Invalid JSON format.'));
        }

        $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();

        foreach ($customers as $customerData) {

            $customer   = $this->customerFactory->create();
            $customer->setEmail($customerData['emailaddress']);
            $customer->setFirstname($customerData['fname']);
            $customer->setLastname($customerData['lname']);
            $hashedPassword = $this->encryptor->getHash('Password@321', true);
            $customer->setWebsiteId($websiteId);
            $customer->setGroupId(1);

            try {
                //$customer->save();
                $this->customerRepository->save($customer, $hashedPassword);
                $output->writeln(__('Customer %1 imported successfully.', $customer->getEmail()));
            } catch (\Exception $e) {
                $output->writeln(__('Failed to import customer %1: %2', $customer->getEmail(), $e->getMessage()));
            }
        }

       $output->writeln("<info>Customer import from JSON completed.</info>");
    }

}

?>