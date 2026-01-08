<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\QueryBuilder;

/**
 * Trait to share complex recipe filtering logic between repositories.
 */
trait RecipeFilterTrait
{
    /**
     * Applies "AND" logic filtering for Many-To-Many relationships (Tags/Ingredients).
     *
     * @param QueryBuilder $qb The main QueryBuilder
     * @param string $recipeMatchExpression The DQL expression to match the recipe (e.g. 'r.id' or 'r.dish')
     * @param string $matchValue The value to match against (e.g. 'r.id' or 'd')
     * @param iterable $items The collection of Tags or Ingredients
     * @param string $relation The relation name on the Recipe entity ('tags' or 'ingredients')
     * @param string $paramPrefix Prefix for unique parameter names
     */
    protected function applyMandatoryFilter(
        QueryBuilder $qb,
        string $recipeMatchExpression,
        string $matchValue,
        iterable $items,
        string $relation,
        string $paramPrefix
    ): void {
        $itemsArray = is_array($items) ? $items : iterator_to_array($items);
        if (empty($itemsArray)) {
            return;
        }

        $ids = array_map(fn($item) => method_exists($item, 'getId') ? $item->getId() : $item, $itemsArray);
        $idParam = $paramPrefix . 'Ids';
        $countParam = $paramPrefix . 'Count';
        
        // Generate unique aliases for each filter to avoid "alias is already defined" errors
        $subRecipeAlias = 'filter_recipe_' . $paramPrefix;
        $subRelationAlias = 'filter_rel_' . $paramPrefix;

        $qb->andWhere(sprintf(
            'EXISTS (
                SELECT 1 FROM App\Entity\Recipe %s 
                JOIN %s.%s %s 
                WHERE %s.%s = %s 
                AND %s.id IN (:%s) 
                GROUP BY %s.id 
                HAVING COUNT(DISTINCT %s.id) = :%s
            )',
            $subRecipeAlias,
            $subRecipeAlias,
            $relation,
            $subRelationAlias,
            $subRecipeAlias,
            $recipeMatchExpression,
            $matchValue,
            $subRelationAlias,
            $idParam,
            $subRecipeAlias,
            $subRelationAlias,
            $countParam
        ))
        ->setParameter($idParam, $ids)
        ->setParameter($countParam, count($ids));
    }
}