
# ImportCustomer
Import Customer by Csv or Json in Magento 2

# Version Support
This module is built in Magento versio 2.4.6-p3 and php version 8.2


# Command to install module through composer

 ```bash
   composer require deepika-meyan/import-customers
   ```


# Import CLI command

**To Import Customer By CSV file:**
   ```bash
   bin/magento customer:import:sample-csv <filepath>
   ```


**To Import Customer By JSON file:**
   ```bash
   bin/magento customer:import:sample-json <filepath>
   ```

**Example**

```bash
   bin/magento customer:import:sample-csv sample.csv
   ```
   // in root path

```bash
   bin/magento customer:import:sample-json sample.json
   ```
   // in root path





