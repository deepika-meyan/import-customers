<?php

namespace Deepika\ImportCustomers\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Encryption\EncryptorInterface;


class ImportCsvCustomers extends Command
{

    protected $customerFactory;
    protected $state;
    protected $storeManager;
    protected $customerRepository;
    protected $encryptor;

    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerInterfaceFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        State $state,
        EncryptorInterface $encryptor
    ) {
        $this->storeManager     = $storeManager;
        $this->customerFactory  = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->state = $state;
        $this->encryptor = $encryptor;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('customer:import:sample-csv')
            ->setDescription('Import customers from a CSV file')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filePath = $input->getArgument('file');

       
        if (!file_exists($filePath)) {
            $output->writeln("<error>File not found: $filePath</error>");
            return;
        }

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
        return Command::SUCCESS;

    }
}
