<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inbox search matches message bodies with a leading-wildcard LIKE, which no
 * B-tree index can serve — every search scans the team's whole message history.
 *
 * NOTE: adding the first FULLTEXT index to an InnoDB table forces a table
 * rebuild (InnoDB has to add its hidden FTS_DOC_ID column), so this one is slow
 * and blocking on a large `messages` table. Run it in a maintenance window.
 *
 * SQLite has no FULLTEXT, so this is a no-op there and the query falls back to
 * LIKE — see ConversationList::usesFullTextSearch().
 */
return new class extends Migration
{
    private const INDEX = 'messages_content_fulltext';

    public function up(): void
    {
        if (! $this->supported() || $this->indexExists()) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->fullText('content', self::INDEX);
        });
    }

    public function down(): void
    {
        if (! $this->supported() || ! $this->indexExists()) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->dropFullText(self::INDEX);
        });
    }

    private function supported(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function indexExists(): bool
    {
        return ! empty(DB::select('show index from messages where key_name = ?', [self::INDEX]));
    }
};
