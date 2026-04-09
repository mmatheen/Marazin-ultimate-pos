<?php

namespace App\Services;

use App\Helpers\SmsHelper;
use App\Models\Setting;
use App\Models\SmsLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SmsService
{
    private const SINGLE_ENDPOINT = 'https://www.smslenz.lk/api/send-sms';
    private const BULK_ENDPOINT = 'https://www.smslenz.lk/api/send-bulk-sms';

    public function sendSingle(string $phone, string $message): array
    {
        $formattedPhone = SmsHelper::formatPhone($phone);

        if (! $formattedPhone) {
            return $this->storeLog(null, $message, 'failed', 'Invalid phone number format.');
        }

        $setting = $this->getSetting();

        if (! $this->hasCredentials($setting)) {
            return $this->storeLog($formattedPhone, $message, 'failed', 'SMS settings are not configured.');
        }

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post(self::SINGLE_ENDPOINT, [
                    'user_id' => $setting->sms_user_id,
                    'api_key' => $setting->sms_api_key,
                    'sender_id' => $setting->sms_sender_id,
                    'contact' => $formattedPhone,
                    'message' => $message,
                ]);

            return $this->storeLog(
                $formattedPhone,
                $message,
                $response->successful() ? 'success' : 'failed',
                $this->responseToString($response->json() ?? $response->body())
            );
        } catch (ConnectionException $exception) {
            return $this->storeLog($formattedPhone, $message, 'failed', $exception->getMessage());
        } catch (\Throwable $exception) {
            return $this->storeLog($formattedPhone, $message, 'failed', $exception->getMessage());
        }
    }

    public function sendBulk(array $phones, string $message): array
    {
        $formattedPhones = collect($phones)
            ->map(fn ($phone) => SmsHelper::formatPhone($phone))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($formattedPhones)) {
            return $this->storeLog(null, $message, 'failed', 'No valid phone numbers provided.');
        }

        $setting = $this->getSetting();

        if (! $this->hasCredentials($setting)) {
            return $this->storeLog(json_encode($formattedPhones), $message, 'failed', 'SMS settings are not configured.');
        }

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post(self::BULK_ENDPOINT, [
                    'user_id' => $setting->sms_user_id,
                    'api_key' => $setting->sms_api_key,
                    'sender_id' => $setting->sms_sender_id,
                    'contacts' => json_encode($formattedPhones, JSON_UNESCAPED_SLASHES),
                    'message' => $message,
                ]);

            return $this->storeLog(
                json_encode($formattedPhones, JSON_UNESCAPED_SLASHES),
                $message,
                $response->successful() ? 'success' : 'failed',
                $this->responseToString($response->json() ?? $response->body())
            );
        } catch (ConnectionException $exception) {
            return $this->storeLog(json_encode($formattedPhones), $message, 'failed', $exception->getMessage());
        } catch (\Throwable $exception) {
            return $this->storeLog(json_encode($formattedPhones), $message, 'failed', $exception->getMessage());
        }
    }

    private function getSetting(): ?Setting
    {
        return Setting::first();
    }

    private function hasCredentials(?Setting $setting): bool
    {
        return $setting
            && filled($setting->sms_user_id)
            && filled($setting->sms_api_key)
            && filled($setting->sms_sender_id);
    }

    private function storeLog(?string $phone, string $message, string $status, string $response): array
    {
        $log = SmsLog::create([
            'phone' => $phone,
            'message' => $message,
            'status' => $status,
            'response' => $response,
        ]);

        return [
            'success' => $status === 'success',
            'status' => $status,
            'log' => $log,
            'response' => $response,
        ];
    }

    private function responseToString(mixed $response): string
    {
        if (is_string($response)) {
            return $response;
        }

        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
