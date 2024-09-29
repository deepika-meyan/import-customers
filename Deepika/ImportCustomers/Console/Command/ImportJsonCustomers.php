<?php
namespace Deepika\ImportCustomers\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Encryption\EncryptorInterface;


class ImportJsonCustomers extends Command
{
    protected $filesystem;
    protected $customerFactory;
    protected $state;
    protected $storeManager;
    protected $customerRepository;
    protected $encryptor;

    public function __construct(
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        CustomerInterfaceFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        State $state,
        EncryptorInterface $encryptor
    ) {
        $this->filesystem = $filesystem;
        $this->storeManager     = $storeManager;
        $this->customerFactory  = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->state = $state;
        $this->encryptor = $encryptor;
        parent::__construct();
    }

    protected function configure()
    {
           $this->setName('customer:import:sample-json')
            ->setDescription('Import customers from a Json file')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the Json file');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('file');

        if (!file_exists($filename)) {
            throw new LocalizedException(__('File not found: %1', $filename));
        }

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

        return Command::SUCCESS;
    }
}
