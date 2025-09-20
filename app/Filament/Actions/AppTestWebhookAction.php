<?php

namespace App\Filament\Actions;

use App\Enums\AppStatusEnum;
use App\Enums\PayTypeEnum;
use App\Models\App;
use App\Services\ApplePayService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Readdle\AppStoreServerAPI\Environment;

class AppTestWebhookAction
{
    public static function make()
    {
        return Action::make(__('test_webhook'))
            ->icon('heroicon-m-pencil-square')
            ->color('success')
            ->modalHeading('Check webhook address in app store')
            ->modalDescription(function (App $app) {
                $url = sprintf('%s/apps/%d/webhook', config('app.url'), $app->id);
                $html = <<<HTML
1. Navigate to Keys: From the main menu, go to "Users and Access" > "Integrations" > "App Store Connect API".<br>
2. App Store Server Notifications: <span style="color: dodgerblue; font-weight: bold;">{$url}</span>
HTML;
                return new HtmlString($html);
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
                    $api = ApplePayService::make($app, Environment::SANDBOX);
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
