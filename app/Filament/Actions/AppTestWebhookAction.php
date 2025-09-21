<?php

namespace App\Filament\Actions;

use App\Enums\AppStatusEnum;
use App\Models\App;
use App\Services\IapService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Readdle\AppStoreServerAPI\Environment;

class AppTestWebhookAction
{
    public static function make()
    {
        return Action::make(__('config_webhook'))
            ->icon('heroicon-m-paper-airplane')
            ->color('success')
            ->modalHeading('Check webhook address in app store')
            ->modalDescription(function (App $app) {
                $url = sprintf('%s/api/v1/apps/%d/webhook', rtrim(config('app.url'), '/'), $app->id);
                return new HtmlString(__('apple_config_hits', ['url' => $url]));
            })
            ->fillForm(fn (App $record): array => $record->toArray())
            ->schema([
                Grid::make(2)
                    ->schema([
                        Section::make('Info')
                            ->schema([
                                TextInput::make('bundle_id'),
                                TextInput::make('issuer_id'),
                                TextInput::make('key_id'),
                            ]),
                        Section::make('Key')
                            ->schema([
                                Textarea::make('p8_key')
                                    ->rows(10)
                                    ->helperText('-----BEGIN PRIVATE KEY-----\n<base64-encoded private key goes here>\n-----END PRIVATE KEY-----')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->successNotificationTitle('Saved')
            ->action(function (array $data, App $app, Action $action) {

                // test request apple server
                $app->forceFill($data);

                try {
                    $api = IapService::makeApi($app, Environment::SANDBOX);
                    $resp = $api->requestTestNotification();
                    $app->test_notification_token = $resp->getTestNotificationToken();
                } catch (\Exception $e) {
                    Log::error($e);
                    $app->status = AppStatusEnum::UN_VERIFIED;
                    $action->failureNotificationTitle($e->getMessage())
                        ->sendFailureNotification()
                        ->halt();
                }

                $app->status = AppStatusEnum::WEB_HOOKING;
                $app->save();
            });
    }
}
