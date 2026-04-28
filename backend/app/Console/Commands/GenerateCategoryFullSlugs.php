<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class GenerateCategoryFullSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:generate-full-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate full_slug for all existing categories';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating full slugs for categories...');

        // First, update parent categories (those without parent_id)
        $parentCategories = Category::whereNull('parent_id')->get();

        foreach ($parentCategories as $category) {
            $category->full_slug = $category->slug;
            $category->saveQuietly();
            $this->line("Updated: {$category->name} -> {$category->full_slug}");

            // Update children
            $this->updateChildren($category);
        }

        $this->info('Done! All category full_slugs have been generated.');

        return Command::SUCCESS;
    }

    /**
     * Recursively update children categories
     */
    private function updateChildren(Category $parent): void
    {
        foreach ($parent->children as $child) {
            $child->full_slug = $parent->full_slug . '/' . $child->slug;
            $child->saveQuietly();
            $this->line("  Updated: {$child->name} -> {$child->full_slug}");

            // Recursively update grandchildren
            $this->updateChildren($child);
        }
    }
}
