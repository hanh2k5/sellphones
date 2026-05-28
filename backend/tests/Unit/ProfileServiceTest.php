<?php

namespace Tests\Unit;

use App\Services\ProfileService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    public function test_update_info_accepts_matching_or_newer_client_timestamp(): void
    {
        $user = new class {
            public $updated_at;
            public array $attributes = [];

            public function __construct()
            {
                $this->updated_at = Carbon::parse('2024-01-01 10:00:00');
            }

            public function update(array $data): void
            {
                $this->attributes = $data;
            }
        };

        $service = new ProfileService();
        $service->updateInfo($user, ['name' => 'Updated'], '2024-01-01T10:00:00+00:00');

        $this->assertSame(['name' => 'Updated'], $user->attributes);
    }

    public function test_update_info_throws_conflict_for_stale_timestamp(): void
    {
        $user = new class {
            public $updated_at;

            public function __construct()
            {
                $this->updated_at = Carbon::parse('2024-01-01 10:00:00');
            }

            public function update(array $data): void
            {
            }
        };

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Hồ sơ đã được cập nhật ở nơi khác. Vui lòng tải lại dữ liệu.');

        (new ProfileService())->updateInfo($user, ['name' => 'Updated'], '2023-12-31T09:59:00+00:00');
    }

    public function test_update_password_updates_hash_when_current_password_matches(): void
    {
        $user = new class {
            public string $password;

            public function __construct()
            {
                $this->password = password_hash('old-password', PASSWORD_BCRYPT);
            }

            public function update(array $data): void
            {
                $this->password = $data['password'];
            }
        };

        (new ProfileService())->updatePassword($user, 'old-password', 'new-password');

        $this->assertTrue(password_verify('new-password', $user->password));
    }

    public function test_update_password_throws_validation_exception_for_wrong_current_password(): void
    {
        $user = new class {
            public string $password;

            public function __construct()
            {
                $this->password = password_hash('old-password', PASSWORD_BCRYPT);
            }

            public function update(array $data): void
            {
            }
        };

        $this->expectException(ValidationException::class);

        (new ProfileService())->updatePassword($user, 'wrong-password', 'new-password');
    }
}
