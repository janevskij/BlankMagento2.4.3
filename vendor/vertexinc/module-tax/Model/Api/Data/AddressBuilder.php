<?php
/**
 * @author    Blue Acorn iCi <code@blueacornici.com>
 * @copyright 2021 Vertex, Inc. All Rights Reserved.
 */

namespace Vertex\Tax\Model\Api\Data;

use Magento\Directory\Model\Country as CountryModel;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Store\Model\ScopeInterface;
use Vertex\Data\AddressInterface;
use Vertex\Data\AddressInterfaceFactory;
use Vertex\Exception\ConfigurationException;
use Vertex\Tax\Model\Api\Utility\MapperFactoryProxy;
use Vertex\Tax\Model\ZipCodeFixer;

/**
 * Builds an Address object for use with the Vertex SDK
 */
class AddressBuilder
{
    /** @var AddressInterfaceFactory */
    private $addressFactory;

    /** @var string */
    private $city;

    /** @var string */
    private $countryCode;

    /** @var CountryModel */
    private $countryModel;

    /** @var string */
    private $postalCode;

    /** @var string */
    private $regionId;

    /** @var string */
    private $region;

    /** @var string[] */
    private $street;

    /** @var ZipCodeFixer */
    private $zipCodeFixer;

    /** @var StringUtils */
    private $stringUtilities;

    /** @var MapperFactoryProxy */
    private $mapperFactory;

    /** @var string */
    private $scopeCode;

    /** @var string */
    private $scopeType = ScopeInterface::SCOPE_STORE;

    /**
     * @param CountryModel $countryModel
     * @param ZipCodeFixer $zipCodeFixer
     * @param AddressInterfaceFactory $addressFactory
     * @param StringUtils $stringUtils
     * @param MapperFactoryProxy $mapperFactory
     */
    public function __construct(
        CountryModel $countryModel,
        ZipCodeFixer $zipCodeFixer,
        AddressInterfaceFactory $addressFactory,
        StringUtils $stringUtils,
        MapperFactoryProxy $mapperFactory
    ) {
        $this->countryModel = $countryModel;
        $this->zipCodeFixer = $zipCodeFixer;
        $this->addressFactory = $addressFactory;
        $this->stringUtilities = $stringUtils;
        $this->mapperFactory = $mapperFactory;
    }

    /**
     * Build an {@see AddressInterface}
     *
     * @return AddressInterface
     * @throws ConfigurationException
     */
    public function build()
    {
        $country = $this->getCountryInformation($this->countryCode);
        $companyState = $this->region ?: $this->getRegionCodeByCountryAndId($country, $this->regionId);
        $countryName = $country->getData('iso3_code');
        $addressMapper = $this->mapperFactory->getForClass(AddressInterface::class, $this->scopeCode, $this->scopeType);

        /** @var AddressInterface $address */
        $address = $this->addressFactory->create();

        if (!empty($this->street)) {
            foreach ($this->street as $key => $streetLine) {
                $street = $this->stringUtilities->substr($streetLine, 0, $addressMapper->getStreetAddressMaxLength());
                $this->street[$key] = $street;
            }

            $address->setStreetAddress($this->street);
        }
        if (!empty($this->city)) {
            $city = $this->stringUtilities->substr($this->city, 0, $addressMapper->getCityMaxLength());
            $address->setCity($city);
        }
        if ($companyState !== null) {
            $mainDivision = $this->stringUtilities->substr(
                $companyState,
                0,
                $addressMapper->getMainDivisionMaxLength()
            );
            $address->setMainDivision($mainDivision);
        }
        if (!empty($this->postalCode)) {
            $postal = $this->zipCodeFixer->fix($this->postalCode);
            $postalCode = $this->stringUtilities->substr($postal, 0, $addressMapper->getPostalCodeMaxLength());
            $address->setPostalCode($postalCode);
        }
        if (!empty($countryName)) {
            $country = $this->stringUtilities->substr($countryName, 0, $addressMapper->getCountryMaxLength());
            $address->setCountry($country);
        }

        return $address;
    }

    /**
     * Set the City
     *
     * @param string $city
     * @return AddressBuilder
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Set the two-letter Country Code
     *
     * @param string $countryCode
     * @return AddressBuilder
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    /**
     * Set the Postal Code
     *
     * @param string $postalCode
     * @return AddressBuilder
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    /**
     * Set the Region string
     *
     * @param string $region
     * @return AddressBuilder
     */
    public function setRegion($region)
    {
        $this->region = $region;
        return $this;
    }

    /**
     * Set the Region ID
     *
     * @param string $regionId
     * @return AddressBuilder
     */
    public function setRegionId($regionId)
    {
        $this->regionId = $regionId;
        return $this;
    }

    /**
     * Set the Scope Code
     *
     * @param string|null $scopeCode
     * @return AddressBuilder
     */
    public function setScopeCode($scopeCode)
    {
        $this->scopeCode = $scopeCode;
        return $this;
    }

    /**
     * Set the Scope Type
     *
     * @param string|null $scopeType
     * @return AddressBuilder
     */
    public function setScopeType($scopeType)
    {
        $this->scopeType = $scopeType;
        return $this;
    }

    /**
     * Set the Street Address
     *
     * @param string|string[] $rawStreet
     * @return AddressBuilder
     */
    public function setStreet($rawStreet)
    {
        if (!is_array($rawStreet)) {
            $rawStreet = [$rawStreet];
        }

        $street = [];
        foreach ($rawStreet as $rawLine) {
            if (!empty($rawLine)) {
                $street[] = $rawLine;
            }
        }

        $this->street = $street;

        return $this;
    }

    /**
     * Retrieve a country's information given its ID
     *
     * @param string $countryId Two letter country code
     * @return CountryModel
     */
    private function getCountryInformation($countryId)
    {
        return $this->countryModel->loadByCode($countryId);
    }

    /**
     * Retrieve a region's code given its ID
     *
     * @param CountryModel $country
     * @param int $regionId
     *
     * @return string|null
     */
    private function getRegionCodeByCountryAndId(CountryModel $country, $regionId)
    {
        $regions = $country->getRegions();

        if ($regions === null) {
            return null;
        }

        // Linear search used since there exists no RegionInformationAcquirerInterface
        foreach ($regions as $region) {
            if ($region->getId() == $regionId) {
                return $region->getCode();
            }
        }

        return null;
    }
}
