# Recipe Archive MVP

## Core Goal
Build a mobile-friendly web application to store and organize references to recipes (not necessarily the full instructions, but where to find them).

## Data Model & Relationships
1. **Recipe**
    - The core entity.
    - It is a **reference**, pointing to either a **Website Link** or a **Book Reference**.
    - *Constraints:* Belongs to exactly one **Dish**.
2. **Dish**
    - Represents a specific meal (e.g., "Spaghetti Carbonara").
    - Acts as a folder/container for multiple versions of a recipe (e.g., "Grandma's version" vs. "NYT Cooking version").
3. **Ingredients**
    - Recipes are tagged with ingredients to allow filtering (e.g., "Find recipes with Eggplant").
    - A Dish can also be an ingredient (e.g., "Tomato Sauce") making the relationship cyclic.
4. **General Tags**
    - Ad-hoc labels for flexible grouping (e.g., "Quick", "Vegetarian", "Party").

## Key Features (MVP)
- **Mobile-First Design:** Optimized for use "on the go" (e.g., in the grocery store or kitchen).
- **Organization:** Grouping by Dish, filtering by Ingredients, and tagging.
- **Simplicity:** Focus on storing *where* the recipe is, rather than building a complex editor for recipe steps.
- **Simple recipe flow**: Scrape URL and make data suggestion for recipes based on URL.

## Future plans
- Data is publicly available (viewable) but only the owner can modify and delete.
- Recipe/Dish images
