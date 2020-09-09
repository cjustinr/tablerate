<?php

namespace Mjsi\Tablerate\Model\ResourceModel\Carrier\Tablerate\CSV;

use Magento\Framework\Phrase;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\LocationDirectory;

class RowParser extends \Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CSV\RowParser
{

    /**
     * @var LocationDirectory
     */
    private $locationDirectory;

    /**
     * RowParser constructor.
     * @param LocationDirectory $locationDirectory
     */
    public function __construct(LocationDirectory $locationDirectory)
    {
        $this->locationDirectory = $locationDirectory;
    }

    public function parse(
        $rowData,
        $rowNumber,
        $websiteId,
        $conditionShortName,
        $conditionFullName,
        $columnResolver
    ) {
        // validate row
        if (count($rowData) < 5) {
            throw new RowException(
                __(
                    'The Table Rates File Format is incorrect in row number "%1". Verify the format and try again.',
                    $rowNumber
                )
            );
        }
        
        $countryId = $this->getCountryId($rowData, $rowNumber, $columnResolver);
        $regionIds = $this->getRegionIds($rowData, $rowNumber, $columnResolver, $countryId);
        $zipCode = $this->getZipCode($rowData, $columnResolver);
        $conditionValue = $this->getConditionValue($rowData, $rowNumber, $conditionFullName, $columnResolver);
        $price = $this->getPrice($rowData, $rowNumber, $columnResolver);

        $rates = [];
        foreach ($regionIds as $regionId) {
            $rates[] = [
                'website_id' => $websiteId,
                'dest_country_id' => $countryId,
                'dest_region_id' => $regionId,
                'dest_zip' => $zipCode,
                'condition_name' => $conditionShortName,
                'condition_value' => $conditionValue,
                'price' => $price,
            ];
        }

        return $rates;
    }
    
    /**
     * Get country id from provided row data.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @param ColumnResolver $columnResolver
     * @return null|string
     * @throws ColumnNotFoundException
     * @throws RowException
     */
    private function getCountryId($rowData, $rowNumber,$columnResolver)
    {
        
        $countryCode = $columnResolver->getColumnValue(ColumnResolver::COLUMN_COUNTRY, $rowData);
        print_r('ahahhahaha');
        die; 
        // validate country
        if ($this->locationDirectory->hasCountryId($countryCode)) {
            print_r('Country hahaha 1');
        die;
            $countryId = $this->locationDirectory->getCountryId($countryCode);
        } elseif ($countryCode === '*' || $countryCode === '') {
            $countryId = '0';
            print_r('Country hahaha 2');
        die;
        } else {
            throw new RowException(
                __(
                    'The "%1" country in row number "%2" is incorrect. Verify the country and try again.',
                    $countryCode,
                    $rowNumber
                )
            );
            print_r('Country hahaha 3');
        die;
        }
        
        return $countryId;
    }

    /**
     * Retrieve region id from provided row data.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @param ColumnResolver $columnResolver
     * @param int $countryId
     * @return array
     * @throws ColumnNotFoundException
     * @throws RowException
     */
    private function getRegionIds($rowData, $rowNumber, $columnResolver, $countryId)
    {
        
        $regionCode = $columnResolver->getColumnValue(ColumnResolver::COLUMN_REGION, $rowData);
        print_r('Region hahaha');
        die;
        if ($countryId !== '0' && $this->locationDirectory->hasRegionId($countryId, $regionCode)) {
            $regionIds = $this->locationDirectory->getRegionIds($countryId, $regionCode);
        } elseif ($regionCode === '*' || $regionCode === '') {
            $regionIds = [0];
        } else {
            throw new RowException(
                __(
                    'The "%1" region or state in row number "%2" is incorrect. '
                    . 'Verify the region or state and try again.',
                    $regionCode,
                    $rowNumber
                )
            );
        }
        return $regionIds;
    }

    /**
     * Retrieve zip code from provided row data.
     *
     * @param array $rowData
     * @param ColumnResolver $columnResolver
     * @return float|int|null|string
     * @throws ColumnNotFoundException
     */
    private function getZipCode($rowData, $columnResolver)
    {
        $zipCode = $columnResolver->getColumnValue(ColumnResolver::COLUMN_ZIP, $rowData);
        if ($zipCode === '') {
            $zipCode = '*';
        }
        return $zipCode;
    }

    /**
     * Get condition value form provided row data.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @param string $conditionFullName
     * @param ColumnResolver $columnResolver
     * @return bool|float
     * @throws ColumnNotFoundException
     * @throws RowException
     */
    private function getConditionValue($rowData, $rowNumber, $conditionFullName, $columnResolver)
    {
        print_r($conditionFullName);
        die;
        // validate condition value
        $conditionValue = $columnResolver->getColumnValue($conditionFullName, $rowData);
        $value = $this->_parseDecimalValue($conditionValue);
       
        if ($value === false) {
            throw new RowException(
                __(
                    'Please correct %1 "%2" in the Row #%3.',
                    $conditionFullName,
                    $conditionValue,
                    $rowNumber
                )
            );
        }
        
        return $value;
    }

    /**
     * Retrieve price from provided row data.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @param ColumnResolver $columnResolver
     * @return bool|float
     * @throws ColumnNotFoundException
     * @throws RowException
     */
    private function getPrice($rowData, $rowNumber, $columnResolver)
    {
        $priceValue = $columnResolver->getColumnValue(ColumnResolver::COLUMN_PRICE, $rowData);
        $price = $this->_parseDecimalValue($priceValue);
        if ($price === false) {
            throw new RowException(
                __(
                    'The "%1" shipping price in row number "%2" is incorrect. Verify the shipping price and try again.',
                    $priceValue,
                    $rowNumber
                )
            );
        }
        return $price;
    }

    /**
     * Parse and validate positive decimal value
     *
     * Return false if value is not decimal or is not positive
     *
     * @param string $value
     * @return bool|float
     */
    private function _parseDecimalValue($value)
    {
        $result = false;
        if (is_numeric($value)) {
            $value = (double)sprintf('%.4F', $value);
            if ($value >= 0.0000) {
                $result = $value;
            }
        }
        return $result;
    }
}