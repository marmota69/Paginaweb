<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('profile_name')->default('');
            $table->string('profile_initials', 8)->default('');
            $table->unsignedSmallInteger('profile_year')->default(2026);
            $table->string('photo_path')->nullable();

            // Hero
            $table->json('hero_kicker')->nullable();
            $table->json('hero_title')->nullable();
            $table->json('hero_lead')->nullable();

            // About
            $table->json('about_title')->nullable();
            $table->json('about_body')->nullable();

            // Section headings and intros
            $table->json('projects_title')->nullable();
            $table->json('projects_intro')->nullable();
            $table->json('experience_title')->nullable();
            $table->json('skills_title')->nullable();
            $table->json('courses_title')->nullable();
            $table->json('courses_intro')->nullable();
            $table->json('guides_title')->nullable();
            $table->json('guides_intro')->nullable();
            $table->json('contact_title')->nullable();
            $table->json('contact_intro')->nullable();

            // Contact
            $table->string('contact_email')->default('');
            $table->json('contact_location')->nullable();
            $table->string('github_url')->nullable();
            $table->string('github_label')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('linkedin_label')->nullable();

            // Footer and SEO
            $table->json('footer_tagline')->nullable();
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->string('seo_image_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
