<?php

namespace Tests\Feature\Api;

use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LampaApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
        ]);
    }

    public function test_device_code_flow_returns_token_and_profile(): void
    {
        $manual = $this->postJson('/api/device/code/manual', [
            'email' => $this->user->email,
        ]);

        $manual->assertOk()->assertJsonPath('secuses', true);

        $auth = $this->postJson('/api/device/add', [
            'code' => $manual->json('code'),
        ]);

        $auth->assertOk()
            ->assertJsonStructure(['token', 'email', 'profile' => ['id', 'name', 'icon', 'main', 'child', 'age']])
            ->assertJsonPath('email', $this->user->email)
            ->assertJsonPath('profile.name', 'Owner');

        $this->getJson('/api/users/get', [
            'token' => $auth->json('token'),
        ])->assertOk()->assertJsonPath('user.email', $this->user->email);
    }

    public function test_bookmark_dump_and_changelog_follow_profile_header(): void
    {
        [$token, $profileId] = $this->authenticate();

        $this->postJson('/api/bookmarks/add', [
            'type' => 'book',
            'card_id' => 123,
            'data' => '{"id":123,"title":"Movie"}',
        ], [
            'token' => $token,
            'profile' => (string) $profileId,
        ])->assertOk()->assertJsonPath('secuses', true);

        $dump = $this->get('/api/bookmarks/dump', [
            'token' => $token,
            'profile' => (string) $profileId,
        ]);

        $dump->assertOk();
        $dumpPayload = json_decode($dump->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $dumpPayload['version']);
        $this->assertCount(1, $dumpPayload['bookmarks']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dumpPayload['bookmarks'][0]['time']);

        $change = $this->getJson('/api/bookmarks/changelog?since=0', [
            'token' => $token,
            'profile' => (string) $profileId,
        ]);

        $change->assertOk()
            ->assertJsonPath('version', 1)
            ->assertJsonPath('changelog.0.action', 'add');

        $this->postJson('/api/bookmarks/add', [
            'type' => 'book',
            'card_id' => 123,
            'data' => '{"id":123,"title":"Movie 2"}',
        ], [
            'token' => $token,
            'profile' => (string) $profileId,
        ])->assertOk()->assertJsonPath('secuses', true);

        $this->postJson('/api/bookmarks/clear', [
            'type' => 'group',
            'group' => 'book',
        ], [
            'token' => $token,
            'profile' => (string) $profileId,
        ])->assertOk()->assertJsonPath('secuses', true);

        $change = $this->getJson('/api/bookmarks/changelog?since=1', [
            'token' => $token,
            'profile' => (string) $profileId,
        ]);

        $change->assertOk()
            ->assertJsonPath('version', 3)
            ->assertJsonPath('changelog.0.action', 'update')
            ->assertJsonPath('changelog.1.action', 'clear')
            ->assertJsonPath('changelog.1.entity_id', null);
    }

    public function test_bookmark_sync_supports_grouped_lampa_payload(): void
    {
        [$token, $profileId] = $this->authenticate();

        $file = UploadedFile::fake()->createWithContent('bookmarks.json', json_encode([
            'card' => [
                [
                    'id' => 1199193,
                    'title' => 'Pose',
                ],
                [
                    'id' => 680493,
                    'title' => 'Return to Silent Hill',
                ],
            ],
            'like' => [],
            'wath' => [1199193],
            'book' => [680493],
            'history' => [680493, 1199193],
            'look' => [],
            'viewed' => [],
            'scheduled' => [],
            'continued' => [],
            'thrown' => [],
        ], JSON_THROW_ON_ERROR));

        $this->post('/api/bookmarks/sync', [
            'file' => $file,
        ], [
            'token' => $token,
            'profile' => (string) $profileId,
        ])->assertOk()->assertJsonPath('secuses', true);

        $this->assertSame(4, Bookmark::query()->count());
        $this->assertDatabaseHas('bookmarks', [
            'profile_id' => $profileId,
            'card_id' => 1199193,
            'type' => 'wath',
        ]);
        $this->assertDatabaseHas('bookmarks', [
            'profile_id' => $profileId,
            'card_id' => 680493,
            'type' => 'book',
        ]);
        $this->assertDatabaseHas('bookmarks', [
            'profile_id' => $profileId,
            'card_id' => 680493,
            'type' => 'history',
        ]);
        $this->assertDatabaseHas('bookmarks', [
            'profile_id' => $profileId,
            'card_id' => 1199193,
            'type' => 'history',
        ]);
        $this->assertDatabaseMissing('bookmarks', [
            'profile_id' => $profileId,
            'card_id' => 0,
        ]);
    }

    public function test_timeline_dump_and_changelog_track_versions(): void
    {
        [$token, $profileId] = $this->authenticate();

        $this->postJson('/api/timeline/update', [
            'hash' => 'hash1',
            'percent' => 80,
            'time' => 3200,
            'duration' => 4000,
        ], [
            'token' => $token,
            'profile' => (string) $profileId,
        ])->assertOk()
            ->assertJsonPath('secuses', true)
            ->assertJsonPath('version', 1)
            ->assertJsonPath('timeline.profile', $profileId);

        $dump = $this->get('/api/timeline/dump', [
            'token' => $token,
            'profile' => (string) $profileId,
        ]);

        $payload = json_decode($dump->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $payload['version']);
        $this->assertSame(80, $payload['timelines']['hash1']['percent']);
        $this->assertSame('hash1', $payload['timelines']['hash1']['hash']);
        $this->assertSame($profileId, $payload['timelines']['hash1']['profile']);

        $this->getJson('/api/timeline/changelog?since=0', [
            'token' => $token,
            'profile' => (string) $profileId,
        ])->assertOk()
            ->assertJsonPath('version', 1)
            ->assertJsonPath('timelines.hash1.hash', 'hash1')
            ->assertJsonPath('timelines.hash1.percent', 80)
            ->assertJsonPath('timelines.hash1.profile', $profileId);

        $emptyChange = $this->getJson('/api/timeline/changelog?since=1', [
            'token' => $token,
            'profile' => (string) $profileId,
        ])->assertOk()
            ->assertJsonPath('version', 1);

        $this->assertStringContainsString('"timelines":{}', $emptyChange->getContent());
    }

    public function test_timeline_update_accepts_float_time_values(): void
    {
        [$token, $profileId] = $this->authenticate();

        $this->postJson('/api/timeline/update', [
            'hash' => 'float-hash',
            'percent' => 21.9,
            'time' => 1397.294912,
            'duration' => 6525.824,
        ], [
            'token' => $token,
            'profile' => (string) $profileId,
        ])->assertOk()
            ->assertJsonPath('secuses', true)
            ->assertJsonPath('timeline.hash', 'float-hash')
            ->assertJsonPath('timeline.percent', 21)
            ->assertJsonPath('timeline.time', 1397)
            ->assertJsonPath('timeline.duration', 6525);
    }

    public function test_backup_roundtrip_and_notifications_work(): void
    {
        [$token, $profileId] = $this->authenticate();

        $backupFile = UploadedFile::fake()->createWithContent('backup.json', '{"favorite":"x","file_view_1":"y"}');
        $this->post('/api/users/backup/export', [
            'file' => $backupFile,
        ], [
            'token' => $token,
        ])->assertOk()->assertJsonPath('limited', false);

        $this->getJson('/api/users/backup/import', [
            'token' => $token,
        ])->assertOk()->assertJsonPath('data.favorite', 'x');

        $this->postJson('/api/notifications/add', [
            'voice' => 'en',
            'data' => '{"title":"Ping"}',
            'episode' => 1,
            'season' => 1,
        ], [
            'token' => $token,
            'profile' => (string) $profileId,
        ])->assertOk()->assertJsonPath('limited', false);

        $this->getJson('/api/notifications/all', [
            'token' => $token,
            'profile' => (string) $profileId,
        ])->assertOk()->assertJsonCount(1, 'notifications');
    }

    protected function authenticate(): array
    {
        $code = $this->postJson('/api/device/code/manual', [
            'email' => $this->user->email,
        ])->json('code');

        $auth = $this->postJson('/api/device/add', [
            'code' => $code,
        ]);

        return [$auth->json('token'), $auth->json('profile.id')];
    }
}
