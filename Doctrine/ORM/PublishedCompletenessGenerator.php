<?php

namespace Pim\Bundle\DevToolboxBundle\Doctrine\ORM;

use Pim\Bundle\CatalogBundle\Doctrine\ORM\CompletenessGenerator;
use Pim\Bundle\CatalogBundle\Model\FamilyInterface;

/**
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PublishedCompletenessGenerator extends CompletenessGenerator
{
    /**
     * {@inheritdoc}
     *
     * Overridden due to
     * LEFT JOIN on pimee_workflow_published_product_value_price instead of pim_catalog_product_value_price
     */
    protected function getCompletePricesSQL($criteria = array())
    {
        return <<<COMPLETE_PRICES_SQL
            SELECT l.id AS locale_id, c.id AS channel_id, v.id AS value_id
                FROM pim_catalog_attribute_requirement r
                JOIN %attribute_table% att ON att.id = r.attribute_id AND att.backend_type = "prices"
                JOIN pim_catalog_channel c ON c.id = r.channel_id %channel_conditions%
                JOIN pim_catalog_channel_locale cl ON cl.channel_id = c.id
                JOIN pim_catalog_locale l ON l.id = cl.locale_id
                JOIN pim_catalog_channel_currency ccur ON ccur.channel_id = c.id
                JOIN pim_catalog_currency cur ON cur.id = ccur.currency_id
                JOIN %product_table% p ON p.family_id = r.family_id %product_conditions%
                JOIN %product_value_table% v
                    ON (v.scope_code = c.code OR v.scope_code IS NULL)
                    AND (v.locale_code = l.code OR v.locale_code IS NULL)
                    AND v.attribute_id = att.id
                    AND v.entity_id = p.id
                LEFT JOIN pimee_workflow_published_product_value_price price
                    ON price.value_id = v.id
                    AND price.currency_code = cur.code
                GROUP BY l.id, c.id, v.id
                HAVING COUNT(price.data) = COUNT(price.id)
COMPLETE_PRICES_SQL;
    }

    /**
     * {@inheritdoc}
     *
     * Overridden due to LEFT JOIN pimee_workflow_published_product_completeness instead of pim_catalog_completeness
     */
    protected function getMissingCompletenessesSQL($criteria = array())
    {
        return <<<MISSING_SQL
            SELECT l.id AS locale_id, c.id AS channel_id, p.id AS product_id
            FROM
                (SELECT c.id, r.family_id
                FROM pim_catalog_attribute_requirement r
                JOIN pim_catalog_channel c ON c.id = r.channel_id %channel_conditions%
                GROUP BY c.id, r.family_id) AS c
            JOIN pim_catalog_channel_locale cl ON cl.channel_id = c.id
            JOIN pim_catalog_locale l ON l.id = cl.locale_id
            JOIN %product_table% p ON p.family_id = c.family_id %product_conditions%
            LEFT JOIN pimee_workflow_published_product_completeness co
                ON co.product_id = p.id
                AND co.channel_id = c.id
                AND co.locale_id = l.id
            WHERE co.id IS NULL
MISSING_SQL;
    }

    /**
     * {@inheritdoc}
     *
     * Overridden due to INSERT INTO pimee_workflow_published_product_completeness instead of pim_catalog_completeness
     */
    protected function getMainSqlPart()
    {
        return <<<MAIN_SQL
            INSERT INTO pimee_workflow_published_product_completeness (
                locale_id, channel_id, product_id, ratio, missing_count, required_count
            )
            SELECT
                l.id AS locale_id, c.id AS channel_id, p.id AS product_id,
                (
                    COUNT(distinct v.id)
                    / (
                        SELECT count(*)
                            FROM pim_catalog_attribute_requirement
                            WHERE family_id = p.family_id
                                AND channel_id = c.id
                                AND required = true
                    )
                    * 100
                ) AS ratio,
                (
                    (
                        SELECT count(*)
                            FROM pim_catalog_attribute_requirement
                            WHERE family_id = p.family_id
                                AND channel_id = c.id
                                AND required = true
                    ) - COUNT(distinct v.id)
                ) AS missing_count,
                (
                    SELECT count(*)
                        FROM pim_catalog_attribute_requirement
                        WHERE family_id = p.family_id
                            AND channel_id = c.id
                            AND required = true
                ) AS required_count
            FROM missing_completeness m
                JOIN pim_catalog_channel c ON c.id = m.channel_id
                JOIN pim_catalog_locale l ON l.id = m.locale_id
                JOIN %product_table% p ON p.id = m.product_id
                JOIN pim_catalog_attribute_requirement r ON r.family_id = p.family_id AND r.channel_id = c.id
                JOIN %product_value_table% v ON v.attribute_id = r.attribute_id
                    AND (v.scope_code = c.code OR v.scope_code IS NULL)
                    AND (v.locale_code = l.code OR v.locale_code IS NULL)
                    AND v.entity_id = p.id
                LEFT JOIN complete_price
                    ON complete_price.value_id = v.id
                    AND complete_price.channel_id = c.id
                    AND complete_price.locale_id = l.id
                %product_value_joins%
            WHERE (%product_value_conditions% OR complete_price.value_id IS NOT NULL) AND r.required = true
            GROUP BY p.id, c.id, l.id
MAIN_SQL;
    }

    /**
     * {@inheritdoc}
     *
     * Overridden due to hardcoded class names
     */
    protected function getClassContentFields($className, $prefix)
    {
        switch ($className) {
            case 'PimEnterprise\Bundle\WorkflowBundle\Model\PublishedProductMetric':
                return array(sprintf('%s.%s', $prefix, 'data'));
            case 'PimEnterprise\Bundle\WorkflowBundle\Model\PublishedProductPrice':
                return array();
            case 'PimEnterprise\Bundle\WorkflowBundle\Model\PublishedProductMedia':
                return array(sprintf('%s.%s', $prefix, 'filename'));
            default:
                return array_map(
                    function ($name) use ($prefix) {
                        return sprintf('%s.%s', $prefix, $name);
                    },
                    array_filter(
                        $this->getClassMetadata($className)->getColumnNames(),
                        function ($value) {
                            return (strpos($value, 'value_') === 0);
                        }
                    )
                );
        }
    }

    /**
     * {@inheritdoc}
     *
     * Overridden due to hardcoded class names
     */
    protected function getAssociationJoins($mapping, $prefix)
    {
        if (in_array($mapping['fieldName'], $this->getSkippedMappings())) {
            return array();
        }

        if ($mapping['targetEntity'] === 'PimEnterprise\Bundle\WorkflowBundle\Model\PublishedProductPrice') {
            return array();
        }

        return parent::getAssociationJoins($mapping, $prefix);
    }

    /**
     * {@inheritdoc}
     *
     * Overridden due to INSERT INTO pimee_workflow_published_product_completeness instead of pim_catalog_completeness
     */
    public function scheduleForFamily(FamilyInterface $family)
    {
        $sql = '
            DELETE c FROM pimee_workflow_published_product_completeness c
              JOIN %product_table% p ON p.id = c.product_id
             WHERE p.family_id = :family_id';

        $sql = $this->applyTableNames($sql);

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('family_id', $family->getId());

        $stmt->execute();
    }
}
