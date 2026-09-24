<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bulk e-mail to people who have already bought.
 *
 * A campaign keeps its own recipient rows rather than resolving the audience
 * again at send time. The audience is a query over live order data, so it
 * moves between the moment someone presses send and the moment the last
 * message goes out; freezing it means the count shown before sending is the
 * count that actually gets sent, a failed address can be retried on its own,
 * and there is a record afterwards of who was written to.
 *
 * Unsubscribes are a suppression list keyed by address, not a flag on a
 * customer, because a buyer here is an address on an order rather than a row
 * in `users` -- guest checkout means most of them have no account at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->string('preheader')->nullable();
            $table->text('body')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();

            $table->string('audience')->default('buyers');
            $table->json('filters')->nullable();
            $table->text('manual_recipients')->nullable();

            $table->string('status')->default('draft');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // The scheduler polls for due campaigns every minute.
            $table->index(['status', 'scheduled_for']);
        });

        Schema::create('email_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // One message per address per campaign, enforced rather than
            // trusted: the resolver de-duplicates, and this is what makes a
            // double send impossible if it ever stops.
            $table->unique(['email_campaign_id', 'email']);
            $table->index(['email_campaign_id', 'status']);
        });

        Schema::create('email_unsubscribes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('email_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('unsubscribed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_unsubscribes');
        Schema::dropIfExists('email_campaign_recipients');
        Schema::dropIfExists('email_campaigns');
    }
};
