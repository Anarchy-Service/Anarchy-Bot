<?php
declare(strict_types=1);

use AnarchyService\Get;
use AnarchyService\Base;
use AnarchyService\DB\DB;
use AnarchyService\SendRequest\Chat;
use AnarchyService\SendRequest\Edit;
use AnarchyService\SendRequest\Send;
use AnarchyService\GoogleTranslate;

require_once 'vendor/autoload.php';

$tg = new Base();
$DB = DB::Database();
if ($argv[1]) {
    $argument = trim($argv[1]);
    if ($argument != '') {
        Get::set(json_decode(file_get_contents($argument)));
        unlink($argument);
    }
} else {
    Get::set($tg->getWebhookUpdates());
}
$sudors = explode(',', getenv('ADMINS'));
$Group = $DB->SelectData('Groups/' . Get::$chat_id, Get::$chat_id, ['Chat_id' => Get::$chat_id]);
if (!$Group) {
    $Administrators = Chat::getChatAdministrators(Get::$chat_id)->result;
    $Admins = Get::$new_chat_member_id ? [Get::$from_id] : [ ];
    foreach ($Administrators as $administrator) {
        if ($administrator->status == 'administrator') {
            $Admins[] = $administrator->user->id;
        } elseif ($administrator->status == 'creator') {
            $Creator = $administrator->user->id;
        }
    }
    $DefaultSettings = [
        'WelcomeMSGStatus' =>
            [
                'Value' => true,
                'name' => 'وضعیت ارسال پیام خوش آمدگویی'
            ],
        'InfoMSGStatus' =>
            [
                'Value' => true,
                'name' => 'وضعیت ارسال پیام اینفو'
            ],
        'ForceTOChannelJoin' =>
            [
                'Value' => true,
                'name' => 'وضعیت عضویت اجباری در کانال'
            ],
        'ForceTOAddInGP' =>
            [
                'Value' => false,
                'name' => 'وضعیت اد اجباری در گروه'
            ],
        'CaptchaStatus' =>
            [
                'Value' => true,
                'name' => 'وضعیت تایید اجباری کپچا'
            ],
        'ConversationStatus' =>
            [
                'Value' => false,
                'name' => 'وضعیت پاسخ دادن ربات'
            ],
        'SpamReportStatus' =>
            [
                'Value' => true,
                'name' => 'وضعیت ارسال گزارش اسپم'
            ],
        'WarnInSpam' =>
            [
                'Value' => true,
                'name' => 'وضعیت اخطار دادن هنگام اسپم'
            ],
        'DelTGServicesStatus' =>
            [
                'Value' => false,
                'name' => 'وضعیت حذف پیام های سرویس تلگرام'
            ],
        'DelLinkStatus' =>
            [
                'Value' => false,
                'name' => 'وضعیت حذف لینک'
            ],
        'DelTGLinkStatus' =>
            [
                'Value' => false,
                'name' => 'وضعیت حذف لینک تلگرامی'
            ],
        'DelMentionStatus' =>
            [
                'Value' => false,
                'name' => 'وضعیت حذف منشن'
            ],
        'DelForwardStatus' =>
            [
                'Value' => false,
                'name' => 'وضعیت حذف فوروارد'
            ],
        'DelFilterWordsStatus' =>
            [
                'Value' => false,
                'name' => 'وضعیت حذف کلمات فیلتر شده'
            ]
    ];
    $DB->CreateTable('Groups/' . Get::$chat_id, Get::$chat_id, [
        'Working' => true,
        'AddAt' => time(),
        'Chat_id' => Get::$chat_id,
        'Chat_title' => Get::$chat_title,
        'ChargeEnd' => 0,
        'BotAdder' => Get::$from_id,
        'Creator' => $Creator,
        'Administrators' => $Admins,
        'WhiteListUsers' => [],
        'WhiteListChannels' => [],
        'WordsFilterList' => [],
        'MSGs' => [
            'WelcomeMSG'          => ['MSG'=>' سلام -MENTION=~NEW_USER_ID~~NEW_FIRST_NAME~- ~ENTER~ به گروه ~GROUP_TITLE~ خوش آمدید. ','name'=>'پیام خوش آمدگویی'],
            'ForceADDMSG'         => ['MSG'=>' سلام -MENTION=~USER_ID~~FIRST_NAME~- ~ENTER~ برای ارسال پیام ابتدا ~SHOULD_ADD_NUMBER~ نفر اد کنید. ~ENTER~ تعداد اد شده توسط شما : ~ADD_NUMBER~','name'=>'پیام اد اجباری'],
            'ForceChannelJoinMSG' => ['MSG'=>' سلام -MENTION=~USER_ID~~FIRST_NAME~- ~ENTER~ برای ارسال پیام ابتدا عضو کانال های زیر شوید. ','name'=>'پیام عضویت اجباری'],
            'CaptchaMSG'          => ['MSG'=>' سلام -MENTION=~USER_ID~~FIRST_NAME~- ~ENTER~ برای ارسال پیام ابتدا حساب کاربری خود را تایید کنید. ~ENTER~ 3/~CAPTCHASENDNUM~ ','name'=>'پیام کپچا'],
            'SpamReportMSG'       => ['MSG'=>' کاربر -MENTION=~USER_ID~~FIRST_NAME~- ~ENTER~ تو گروه اسپم میکنه. ~ENTER~ -MENTION=~CREATOR_ID~ADMIN- ','name'=>'پیام گزارش اسپم'],
            'WarnMSG'             => ['MSG'=>' کاربر -MENTION=~IN_REPLY_ID~~IN_REPLY_FIRST_NAME~- ~ENTER~ یک اخطار به اخطار های شما اضافه شد ~ENTER~ ~USERWARNCOUNT~/~WARNNUMBERTOREMOVE~ ','name'=>'پیام اخطار'],
            'delWarnMSG'          => ['MSG'=>' کاربر -MENTION=~IN_REPLY_ID~~IN_REPLY_FIRST_NAME~- ~ENTER~ یک اخطار از اخطار های شما کسر شد ~ENTER~ ~USERWARNCOUNT~/~WARNNUMBERTOREMOVE~ ','name'=>'پیام حذف اخطار'],
            'BotWarnMSG'          => ['MSG'=>' کاربر -MENTION=~USER_ID~~FIRST_NAME~- ~ENTER~ یک اخطار به اخطار های شما اضافه شد ~ENTER~ ~USERWARNCOUNT~/~WARNNUMBERTOREMOVE~ ','name'=>'پیام اخطار اسپم'],
        ],
        'DelWlcMSGAfter' => 5,
        'DelForceAddMSGAfter' => 5,
        'DelForceJoinMSGAfter' => 5,
        'DelCaptchaMSGAfter' => 5,
        'DelWarnMSGAfter' => 5,
        'DelReportMSGAfter' => 5,
        'SpamNumberToReport' => 5,
        'SpamTimeToReport' => 5,
        'WarnNumberToRemove' => 5,
        'AddNumber' => 5,
        'GPWarns' => 0,
        'GPChannels' => [],
        'PreviousMembersShouldJoin' => false,
        'PreviousMembersShouldAdd' => false,
        'PreviousMembersShouldVerifyCaptcha' => false,
        'Settings' => $DefaultSettings
    ]);
    $msg = 'سلام' . PHP_EOL . 'برای فعال سازی رایگان ربات، من رو به عنوان ادمین گروه انتخاب کنید' . PHP_EOL . 'با فرستادن راهنما هم می‌توانید آموزش استفاده از ربات را یاد بگیرید 😃';
    Send::sendMessage(Get::$chat_id, $msg);
    $Group = $DB->SelectData('Groups/' . Get::$chat_id, Get::$chat_id, ['Chat_id' => Get::$chat_id]);
}
if ($Group->Working) {
    $user_id = Get::$new_chat_member_id ?? Get::$from_id;
    $IsClintBot = $DB->SelectData('Users/BlackDir', 'ClintBotList', ['User_id' => $user_id]);
    $InBlackList = $DB->SelectData('Users/BlackDir', 'BlackList', ['User_id' => $user_id]);
    $User = $DB->SelectData('Users', $user_id, ['User_id' => $user_id]);
    if ($IsClintBot || $InBlackList) {
        Chat::kickChatMember(Get::$chat_id, $user_id);
        Chat::deleteMessage(Get::$chat_id, Get::$message_id);
        die();
    } elseif (!$User) {
        $DB->CreateTable('Users', $user_id, [
            'User_id' => $user_id,
            'MSGCount' => 0,
            'LTU' => 0, # Last Thank UnixTime
            'ThankCount' => 0,
            'CaptchaStatus' => false,
            'CaptchaSendNum' => 0,
        ]);
        $User = $DB->SelectData('Users', $user_id, ['User_id' => $user_id]);
    }
    $Member = $DB->SelectData('Groups/' . Get::$chat_id . '/Users', $user_id, ['User_id' => $user_id]);
    if (!$Member) {
        if (Get::$from_id != $user_id) {
            $adder = $DB->SelectData('Groups/' . Get::$chat_id . '/Users', Get::$from_id, ['User_id' => Get::$from_id]);
            $DB->UpdateData('Groups/' . Get::$chat_id . '/Users', Get::$from_id, ['AddNumber' => ++$adder->AddNumber], ['User_id' => Get::$from_id]);
        }
        $DB->CreateTable('Groups/' . Get::$chat_id . '/Users', $user_id, [
            'User_id' => $user_id,
            'SSCU' => time(), # Start Spam Count UnixTime
            'SpamCount' => 0,
            'WarnCount' => 0,
            'AddNumber' => 0,
            'SendReportAt' => 0,
            'AddDone' => !$Group->PreviousMembersShouldAdd && !Get::$new_chat_member_id,
            'AddedBy' => (Get::$from_id != $user_id) ? Get::$from_id : 0,
        ]);
        $Member = $DB->SelectData('Groups/' . Get::$chat_id . '/Users', $user_id, ['User_id' => $user_id]);
    }
    if (!in_array($user_id, $sudors) && $user_id != $Group->Creator && !in_array($user_id, $Group->Administrators) && !in_array($user_id, $Group->WhiteListUsers) && $user_id != ($id = explode(':', getenv('TOKEN'))[0])) {
        foreach ($Group->MSGs as $key => $value) {
            $text = $value->MSG;
            if (strpos($text, '-MENTION')) {
                $text = preg_replace('/-MENTION=(~.*?~)(.*?)-/', "<a href='tg://user?id=$1'>$2</a>", $text);
            }
            if (strpos($text, '~USER_ID~')) {
                $text = str_replace('~USER_ID~', Get::$from_id, $text);
            }
            if (strpos($text, '~FIRST_NAME~')) {
                $text = str_replace('~FIRST_NAME~', Get::$from_first_name, $text);
            }
            if (strpos($text, '~LAST_NAME~')) {
                $text = str_replace('~LAST_NAME~', Get::$from_last_name, $text);
            }
            if (strpos($text, '~USERNAME~')) {
                $text = str_replace('~USERNAME~', Get::$from_username, $text);
            }
            //
            if (strpos($text, '~GROUP_ID~')) {
                $text = str_replace('~GROUP_ID~', '@' . Get::$chat_id, $text);
            }
            if (strpos($text, '~GROUP_TITLE~')) {
                $text = str_replace('~GROUP_TITLE~', Get::$chat_title, $text);
            }
            if (strpos($text, '~GROUP_USERNAME~')) {
                $text = str_replace('~GROUP_USERNAME~', '@' . Get::$chat_username, $text);
            }
            //
            if (strpos($text, '~NEW_USER_ID~')) {
                $text = str_replace('~NEW_USER_ID~', Get::$new_chat_member_id, $text);
            }
            if (strpos($text, '~NEW_FIRST_NAME~')) {
                $text = str_replace('~NEW_FIRST_NAME~', Get::$new_chat_member_first_name, $text);
            }
            if (strpos($text, '~NEW_LAST_NAME~')) {
                $text = str_replace('~NEW_LAST_NAME~', Get::$new_chat_member_last_name, $text);
            }
            if (strpos($text, '~NEW_USERNAME~')) {
                $text = str_replace('~NEW_USERNAME~', Get::$new_chat_member_username, $text);
            }
            //
            if (strpos($text, '~IN_REPLY_ID~')) {
                $text = str_replace('~IN_REPLY_ID~', Get::$reply_to_from_id, $text);
            }
            if (strpos($text, '~IN_REPLY_FIRST_NAME~')) {
                $text = str_replace('~IN_REPLY_FIRST_NAME~', Get::$reply_to_from_first_name, $text);
            }
            if (strpos($text, '~IN_REPLY_LAST_NAME~')) {
                $text = str_replace('~IN_REPLY_LAST_NAME~', Get::$reply_to_from_last_name, $text);
            }
            if (strpos($text, '~IN_REPLY_USERNAME~')) {
                $text = str_replace('~IN_REPLY_USERNAME~', Get::$reply_to_from_username, $text);
            }
            //
            if (strpos($text, '~ENTER~')) {
                $text = str_replace('~ENTER~', "\n", $text);
            }
            if (strpos($text, '~CREATOR_ID~')) {
                $text = str_replace('~CREATOR_ID~', $Group->Creator, $text);
            }
            if (strpos($text, '~GP_CHANNEL~')) {
                $text = str_replace('~GP_CHANNEL~', $Group->Creator, $text);
            }
            if (strpos($text, '~CAPTCHASENDNUM~')) {
                $text = str_replace('~CAPTCHASENDNUM~', $User->CaptchaSendNum, $text);
            }
            if (strpos($text, '~USERWARNCOUNT~')) {
                $text = str_replace('~USERWARNCOUNT~', $Member->WarnCount, $text);
            }
            if (strpos($text, '~WARNNUMBERTOREMOVE~')) {
                $text = str_replace('~WARNNUMBERTOREMOVE~', $Group->WarnNumberToRemove, $text);
            }
            if (strpos($text, '~ADD_NUMBER~')) {
                $text = str_replace('~ADD_NUMBER~', $Member->AddNumber, $text);
            }
            if (strpos($text, '~SHOULD_ADD_NUMBER~')) {
                $text = str_replace('~SHOULD_ADD_NUMBER~', $Group->AddNumber, $text);
            }
            $Group->MSGs->$key->MSG = $text;
        }
        if (Get::$new_chat_member_id) {
            if ($Group->Settings->WelcomeMSGStatus->Value) {
                $msg = $Group->MSGs->WelcomeMSG->MSG;
                if ($Group->Settings->CaptchaStatus->Value && !$User->CaptchaStatus) {
                    $msg .= PHP_EOL.PHP_EOL.'برای فعالیت در گروه ابتدا حساب خود را تایید کنید.';
                    $markup = Send::InlineKeyboardMarkup([
                            [
                                ['text' => 'من ربات نیستم', 'callback_data' => "/captchaVerify_$user_id"]
                            ]
                        ]);
                    $send_return = Send::sendMessage(Get::$chat_id, $msg, 'HTML', false, false, null, $markup);
                    $DB->UpdateData('Users', $user_id, ['CaptchaSendNum' => ++$User->CaptchaSendNum], ['User_id' => $user_id]);
                    $DB->InsertData('Bot', 'Temp', ['id'=>rand(),'Type'=>'Captcha','Time' => time(),'Message_id' => $send_return->result->message_id,'Chat_id' => Get::$chat_id]);
                } else {
                    $send_return = Send::sendMessage(Get::$chat_id, $msg, 'HTML');
                    $DB->InsertData('Bot', 'Temp', ['id'=>rand(),'Type'=>'Welcome','Time' => time(),'Message_id' => $send_return->result->message_id,'Chat_id' => Get::$chat_id]);
                }
            } elseif ($Group->Settings->CaptchaStatus->Value && !$User->CaptchaStatus) {
                $send_return = Send::sendMessage(Get::$chat_id, $Group->MSGs->CaptchaMSG->MSG, 'HTML');
                $DB->InsertData('Bot', 'Temp', ['id'=>rand(),'Type'=>'Captcha','Time' => time(),'Message_id' => $send_return->result->message_id,'Chat_id' => Get::$chat_id]);
            }
        } elseif (!Get::$new_chat_member_id && !Get::$left_chat_member_id && strpos(Get::$callback_query_data ?? 'Null', '/captchaVerify_') === false) {
            if ($Group->Settings->CaptchaStatus->Value && !$User->CaptchaStatus) {
                Chat::deleteMessage(Get::$chat_id, Get::$message_id);
                if ($User->CaptchaSendNum <= 3) {
                    $markup = Send::InlineKeyboardMarkup([
                        [
                            ['text' => 'من ربات نیستم', 'callback_data' => "/captchaVerify_$user_id"]
                        ]
                    ]);
                    $send_return = Send::sendMessage(Get::$chat_id, $Group->MSGs->CaptchaMSG->MSG, 'HTML', false, false, null, $markup);
                    $DB->UpdateData('Users', $user_id, ['CaptchaSendNum' => ++$User->CaptchaSendNum], ['User_id' => $user_id]);
                    $DB->InsertData('Bot', 'Temp', ['id'=>rand(),'Type'=>'Captcha','Time' => time(),'Message_id' => $send_return->result->message_id,'Chat_id' => Get::$chat_id]);
                } else {
                    $DB->InsertData('Users/BlackDir', 'ClintBotList', ['User_id' => $user_id, 'Time' => time()]);
                    Chat::kickChatMember(Get::$chat_id, $user_id);
                }
                die();
            } elseif ($Group->Settings->ForceTOAddInGP->Value && $Group->AddNumber > $Member->AddNumber) {
                Chat::deleteMessage(Get::$chat_id, Get::$message_id);
                $send_return = Send::sendMessage(Get::$chat_id, $Group->MSGs->ForceADDMSG->MSG, 'HTML');
                $DB->InsertData('Bot', 'Temp', ['id'=>rand(),'Type'=>'ForceTOAddInGP','Time' => time(),'Message_id' => $send_return->result->message_id,'Chat_id' => Get::$chat_id]);
                die();
            } elseif ($Group->Settings->DelLinkStatus->Value && in_array('url', array_column(Get::$entities, 'type'))) {
                Chat::deleteMessage(Get::$chat_id, Get::$message_id);
                die();
            } elseif ($Group->Settings->DelTGLinkStatus->Value && (strpos('t.me', Get::$text ?? Get::$caption) || strpos('telegram.me', Get::$text ?? Get::$caption) || strpos('telegram.dog', Get::$text ?? Get::$caption))) {
                Chat::deleteMessage(Get::$chat_id, Get::$message_id);
                die();
            } elseif ($Group->Settings->DelMentionStatus->Value && in_array('mention', array_column(Get::$entities, 'type'))) {
                Chat::deleteMessage(Get::$chat_id, Get::$message_id);
                die();
            } elseif ($Group->Settings->DelForwardStatus->Value && Get::$forward_from_chat_type == 'channel' && !in_array(Get::$forward_from_chat_username, $Group->WhiteListChannels)) {
                Chat::deleteMessage(Get::$chat_id, Get::$message_id);
                die();
            } elseif ($Group->Settings->ForceTOChannelJoin->Value) {
                $Channels="\n\n";
                $NotJoin = false;
                foreach ($Group->GPChannels as $Channel) {
                    $res = Chat::getChatMember('@'.$Channel, $user_id);
                    if ($res->ok) {
                        if ($res->result->status == 'left' || $res->result->status == 'kicked') {
                            $NotJoin = true;
                            $Channels .="@".$Channel."\n";
                        }
                    }
                }
                if ($NotJoin) {
                    Chat::deleteMessage(Get::$chat_id, Get::$message_id);
                    $send_return = Send::sendMessage(Get::$chat_id, $Group->MSGs->ForceChannelJoinMSG->MSG.$Channels, 'HTML');
                    $DB->InsertData('Bot', 'Temp', ['id'=>rand(),'Type'=>'ForceTOChannelJoin','Time' => time(),'Message_id' => $send_return->result->message_id,'Chat_id' => Get::$chat_id]);
                }
            }
            if ($Group->Settings->DelFilterWordsStatus->Value) {
                foreach ($Group->WordsFilterList as $word) {
                    if (preg_match("~$word~i", Get::$text)) {
                        Chat::deleteMessage(Get::$chat_id, Get::$message_id);
                        die();
                    }
                }
            }
            if ($Group->Settings->SpamReportStatus->Value || $Group->Settings->WarnInSpam->Value) {
                $DB->UpdateData('Groups/' . Get::$chat_id . '/Users', $user_id, ['SpamCount' => ++$Member->SpamCount], ['User_id' => $user_id]);
                if ($Member->SpamCount >= $Group->SpamNumberToReport && ($Member->SSCU + $Group->SpamTimeToReport) > time()) {
                    if ($Group->Settings->WarnInSpam->Value) {
                        $DB->UpdateData('Groups/' . Get::$chat_id . '/Users', $user_id, ['WarnCount' => ++$Member->WarnCount], ['User_id' => $user_id]);
                        if ($Member->WarnCount > $Group->WarnNumberToRemove) {
                            Chat::kickChatMember(Get::$chat_id, $user_id);
                        }
                        $send_return = Send::sendMessage(Get::$chat_id, $Group->MSGs->BotWarnMSG->MSG, 'HTML');
                        $DB->InsertData('Bot', 'Temp', ['id'=>rand(),'Type'=>'Warn','Time' => time(),'Message_id' => $send_return->result->message_id,'Chat_id' => Get::$chat_id]);
                    }
                    if ($Group->Settings->SpamReportStatus->Value) {
                        $send_return = Send::sendMessage(Get::$chat_id, $Group->MSGs->SpamReportMSG->MSG, 'HTML');
                        $DB->InsertData('Bot', 'Temp', ['id'=>rand(),'Type'=>'Report','Time' => time(),'Message_id' => $send_return->result->message_id,'Chat_id' => Get::$chat_id]);
                    }
                    $DB->UpdateData('Groups/' . Get::$chat_id . '/Users', $user_id, ['SpamCount' => 0,'SSCU' => time()], ['User_id' => $user_id]);
                    $res = Chat::restrictChatMember(Get::$chat_id, $user_id, ['can_send_messages' => false], time()+5*60);
                    die();
                } elseif (($Member->SSCU + $Group->SpamTimeToReport) < time()) {
                    $DB->UpdateData('Groups/' . Get::$chat_id . '/Users', $user_id, ['SpamCount' => 0,'SSCU' => time()], ['User_id' => $user_id]);
                }
            }
        }
    } elseif (!in_array($user_id, $Group->WhiteListUsers)) {
        if (Get::$reply_to_from_id) {
            if (in_array($user_id, $sudors) && Get::$text == '!ثبت کریتور') {
                $Group->Creator = Get::$reply_to_from_id;
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['Settings' => $Group->Settings], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'کریتور با موفقیت ثبت شد', null, false, false, Get::$message_id);
            } elseif (in_array($user_id, $sudors) && Get::$text == '!افزودن به لیست سیاه') {
                if (Get::$reply_to_from_id != ($id = explode(':', getenv('TOKEN'))[0]) && !in_array(Get::$reply_to_from_id, $sudors)) {
                    $InBlackList = $DB->SelectData('Users/BlackDir', 'BlackList', ['User_id' => Get::$reply_to_from_id]);
                    if (!$InBlackList) {
                        $res = Chat::kickChatMember(Get::$chat_id, Get::$reply_to_from_id);
                        $DB->InsertData('Users/BlackDir', 'BlackList', ['User_id' => Get::$reply_to_from_id, 'Time' => time()]);
                        if ($res->ok) {
                            Send::sendMessage(Get::$chat_id, 'کاربر به لیست سیاه اضافه و از گروه ریمو شد', null, false, false, Get::$message_id);
                        } else {
                            Send::sendMessage(Get::$chat_id, 'کاربر به لیست سیاه اضافه شد اما به علت :'.PHP_EOL.$res->description.PHP_EOL.'از گروه ریمو نشد', null, false, false, Get::$message_id);
                        }
                    } else {
                        Send::sendMessage(Get::$chat_id, 'کاربر از قبل در لیست سیاه وجود داشت', null, false, false, Get::$message_id);
                    }
                } else {
                    Send::sendMessage(Get::$chat_id, 'امکان مسدود سازی ادمین وجود ندارد!', null, false, false, Get::$message_id);
                }
            } elseif (in_array($user_id, $sudors) && Get::$text == '!حذف از لیست سیاه') {
                $DB->DeleteData('Users/BlackDir', 'BlackList', ['User_id' => Get::$reply_to_from_id]);
                Send::sendMessage(Get::$chat_id, 'کاربر از لیست سیاه حذف شد', null, false, false, Get::$message_id);
            } elseif ((in_array($user_id, $sudors) || $user_id == $Group->Creator) && Get::$text == '!ثبت ادمین') {
                if (!in_array(Get::$reply_to_from_id, $Group->Administrators)) {
                    $Group->Administrators[] = Get::$reply_to_from_id;
                    $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['Administrators' => $Group->Administrators], ['Chat_id' => Get::$chat_id]);
                    Send::sendMessage(Get::$chat_id, 'ادمین با موفقیت ثبت شد', null, false, false, Get::$message_id);
                } else {
                    Send::sendMessage(Get::$chat_id, 'ادمین از قبل وجود داشت', null, false, false, Get::$message_id);
                }
            } elseif ((in_array($user_id, $sudors) || $user_id == $Group->Creator) && Get::$text == '!حذف ادمین') {
                $new_admins = [];
                foreach ($Group->Administrators as $admin) {
                    if ($admin != Get::$reply_to_from_id) {
                        $new_admins[] = $admin;
                    }
                }
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['Administrators' => $new_admins], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'ادمین با موفقیت حذف شد', null, false, false, Get::$message_id);
            } elseif (Get::$text == '!اخطار') {
                if (Get::$reply_to_from_id != ($id = explode(':', getenv('TOKEN'))[0]) && !in_array(Get::$reply_to_from_id, $sudors) && !in_array(Get::$reply_to_from_id, $Group->Administrators)) {
                    $Member = $DB->SelectData('Groups/' . Get::$chat_id . '/Users', Get::$reply_to_from_id, ['User_id' => Get::$reply_to_from_id]);
                    $Member->WarnCount = $Member->WarnCount +1;
                    $text = $Group->MSGs->WarnMSG->MSG;
                    if (strpos($text, '-MENTION')) {
                        $text = preg_replace('/-MENTION=(~.*?~)(.*?)-/', "<a href='tg://user?id=$1'>$2</a>", $text);
                    }
                    if (strpos($text, '~USER_ID~')) {
                        $text = str_replace('~USER_ID~', Get::$from_id, $text);
                    }
                    if (strpos($text, '~FIRST_NAME~')) {
                        $text = str_replace('~FIRST_NAME~', Get::$from_first_name, $text);
                    }
                    if (strpos($text, '~LAST_NAME~')) {
                        $text = str_replace('~LAST_NAME~', Get::$from_last_name, $text);
                    }
                    if (strpos($text, '~USERNAME~')) {
                        $text = str_replace('~USERNAME~', Get::$from_username, $text);
                    }
                    //
                    if (strpos($text, '~GROUP_ID~')) {
                        $text = str_replace('~GROUP_ID~', '@' . Get::$chat_id, $text);
                    }
                    if (strpos($text, '~GROUP_TITLE~')) {
                        $text = str_replace('~GROUP_TITLE~', Get::$chat_title, $text);
                    }
                    if (strpos($text, '~GROUP_USERNAME~')) {
                        $text = str_replace('~GROUP_USERNAME~', '@' . Get::$chat_username, $text);
                    }
                    //
                    if (strpos($text, '~NEW_USER_ID~')) {
                        $text = str_replace('~NEW_USER_ID~', Get::$new_chat_member_id, $text);
                    }
                    if (strpos($text, '~NEW_FIRST_NAME~')) {
                        $text = str_replace('~NEW_FIRST_NAME~', Get::$new_chat_member_first_name, $text);
                    }
                    if (strpos($text, '~NEW_LAST_NAME~')) {
                        $text = str_replace('~NEW_LAST_NAME~', Get::$new_chat_member_last_name, $text);
                    }
                    if (strpos($text, '~NEW_USERNAME~')) {
                        $text = str_replace('~NEW_USERNAME~', Get::$new_chat_member_username, $text);
                    }
                    //
                    if (strpos($text, '~IN_REPLY_ID~')) {
                        $text = str_replace('~IN_REPLY_ID~', Get::$reply_to_from_id, $text);
                    }
                    if (strpos($text, '~IN_REPLY_FIRST_NAME~')) {
                        $text = str_replace('~IN_REPLY_FIRST_NAME~', Get::$reply_to_from_first_name, $text);
                    }
                    if (strpos($text, '~IN_REPLY_LAST_NAME~')) {
                        $text = str_replace('~IN_REPLY_LAST_NAME~', Get::$reply_to_from_last_name, $text);
                    }
                    if (strpos($text, '~IN_REPLY_USERNAME~')) {
                        $text = str_replace('~IN_REPLY_USERNAME~', Get::$reply_to_from_username, $text);
                    }
                    //
                    if (strpos($text, '~ENTER~')) {
                        $text = str_replace('~ENTER~', "\n", $text);
                    }
                    if (strpos($text, '~CREATOR_ID~')) {
                        $text = str_replace('~CREATOR_ID~', $Group->Creator, $text);
                    }
                    if (strpos($text, '~GP_CHANNEL~')) {
                        $text = str_replace('~GP_CHANNEL~', $Group->Creator, $text);
                    }
                    if (strpos($text, '~CAPTCHASENDNUM~')) {
                        $text = str_replace('~CAPTCHASENDNUM~', $User->CaptchaSendNum, $text);
                    }
                    if (strpos($text, '~USERWARNCOUNT~')) {
                        $text = str_replace('~USERWARNCOUNT~', $Member->WarnCount, $text);
                    }
                    if (strpos($text, '~WARNNUMBERTOREMOVE~')) {
                        $text = str_replace('~WARNNUMBERTOREMOVE~', $Group->WarnNumberToRemove, $text);
                    }
                    if (strpos($text, '~ADD_NUMBER~')) {
                        $text = str_replace('~ADD_NUMBER~', $Member->AddNumber, $text);
                    }
                    if (strpos($text, '~SHOULD_ADD_NUMBER~')) {
                        $text = str_replace('~SHOULD_ADD_NUMBER~', $Group->AddNumber, $text);
                    }
                    $DB->UpdateData('Groups/' . Get::$chat_id . '/Users', Get::$reply_to_from_id, ['WarnCount' => $Member->WarnCount], ['User_id' =>  Get::$reply_to_from_id]);
                    if ($Member->WarnCount > $Group->WarnNumberToRemove) {
                        Chat::kickChatMember(Get::$chat_id, Get::$reply_to_from_id);
                    }
                    $send_return = Send::sendMessage(Get::$chat_id, $text, 'HTML');
                    $DB->InsertData('Bot', 'Temp', ['id'=>rand(),'Type'=>'Warn','Time' => time(),'Message_id' => $send_return->result->message_id,'Chat_id' => Get::$chat_id]);
                } else {
                    Send::sendMessage(Get::$chat_id, 'امکان محدود سازی ادمین وجود ندارد!', null, false, false, Get::$message_id);
                }
            } elseif (Get::$text == '!حذف اخطار') {
                $Member = $DB->SelectData('Groups/' . Get::$chat_id . '/Users', Get::$reply_to_from_id, ['User_id' => Get::$reply_to_from_id]);
                $Member->WarnCount = $Member->WarnCount -1;
                $text = $Group->MSGs->WarnMSG->MSG;
                if (strpos($text, '-MENTION')) {
                    $text = preg_replace('/-MENTION=(~.*?~)(.*?)-/', "<a href='tg://user?id=$1'>$2</a>", $text);
                }
                if (strpos($text, '~USER_ID~')) {
                    $text = str_replace('~USER_ID~', Get::$from_id, $text);
                }
                if (strpos($text, '~FIRST_NAME~')) {
                    $text = str_replace('~FIRST_NAME~', Get::$from_first_name, $text);
                }
                if (strpos($text, '~LAST_NAME~')) {
                    $text = str_replace('~LAST_NAME~', Get::$from_last_name, $text);
                }
                if (strpos($text, '~USERNAME~')) {
                    $text = str_replace('~USERNAME~', Get::$from_username, $text);
                }
                //
                if (strpos($text, '~GROUP_ID~')) {
                    $text = str_replace('~GROUP_ID~', '@' . Get::$chat_id, $text);
                }
                if (strpos($text, '~GROUP_TITLE~')) {
                    $text = str_replace('~GROUP_TITLE~', Get::$chat_title, $text);
                }
                if (strpos($text, '~GROUP_USERNAME~')) {
                    $text = str_replace('~GROUP_USERNAME~', '@' . Get::$chat_username, $text);
                }
                //
                if (strpos($text, '~NEW_USER_ID~')) {
                    $text = str_replace('~NEW_USER_ID~', Get::$new_chat_member_id, $text);
                }
                if (strpos($text, '~NEW_FIRST_NAME~')) {
                    $text = str_replace('~NEW_FIRST_NAME~', Get::$new_chat_member_first_name, $text);
                }
                if (strpos($text, '~NEW_LAST_NAME~')) {
                    $text = str_replace('~NEW_LAST_NAME~', Get::$new_chat_member_last_name, $text);
                }
                if (strpos($text, '~NEW_USERNAME~')) {
                    $text = str_replace('~NEW_USERNAME~', Get::$new_chat_member_username, $text);
                }
                //
                if (strpos($text, '~IN_REPLY_ID~')) {
                    $text = str_replace('~IN_REPLY_ID~', Get::$reply_to_from_id, $text);
                }
                if (strpos($text, '~IN_REPLY_FIRST_NAME~')) {
                    $text = str_replace('~IN_REPLY_FIRST_NAME~', Get::$reply_to_from_first_name, $text);
                }
                if (strpos($text, '~IN_REPLY_LAST_NAME~')) {
                    $text = str_replace('~IN_REPLY_LAST_NAME~', Get::$reply_to_from_last_name, $text);
                }
                if (strpos($text, '~IN_REPLY_USERNAME~')) {
                    $text = str_replace('~IN_REPLY_USERNAME~', Get::$reply_to_from_username, $text);
                }
                //
                if (strpos($text, '~ENTER~')) {
                    $text = str_replace('~ENTER~', "\n", $text);
                }
                if (strpos($text, '~CREATOR_ID~')) {
                    $text = str_replace('~CREATOR_ID~', $Group->Creator, $text);
                }
                if (strpos($text, '~GP_CHANNEL~')) {
                    $text = str_replace('~GP_CHANNEL~', $Group->Creator, $text);
                }
                if (strpos($text, '~CAPTCHASENDNUM~')) {
                    $text = str_replace('~CAPTCHASENDNUM~', $User->CaptchaSendNum, $text);
                }
                if (strpos($text, '~USERWARNCOUNT~')) {
                    $text = str_replace('~USERWARNCOUNT~', $Member->WarnCount, $text);
                }
                if (strpos($text, '~WARNNUMBERTOREMOVE~')) {
                    $text = str_replace('~WARNNUMBERTOREMOVE~', $Group->WarnNumberToRemove, $text);
                }
                if (strpos($text, '~ADD_NUMBER~')) {
                    $text = str_replace('~ADD_NUMBER~', $Member->AddNumber, $text);
                }
                if (strpos($text, '~SHOULD_ADD_NUMBER~')) {
                    $text = str_replace('~SHOULD_ADD_NUMBER~', $Group->AddNumber, $text);
                }
                $DB->UpdateData('Groups/' . Get::$chat_id . '/Users', Get::$reply_to_from_id, ['WarnCount' => $Member->WarnCount], ['User_id' =>  Get::$reply_to_from_id]);
                $send_return = Send::sendMessage(Get::$chat_id, $Group->MSGs->delWarnMSG->MSG, 'HTML');
                $DB->InsertData('Bot', 'Temp', ['id'=>rand(),'Type'=>'Warn','Time' => time(),'Message_id' => $send_return->result->message_id,'Chat_id' => Get::$chat_id]);
            } elseif (Get::$text == '!افزودن به لیست سفید') {
                if (!in_array(Get::$reply_to_from_id, $Group->WhiteListUsers)) {
                    $Group->WhiteListUsers[] = Get::$reply_to_from_id;
                    $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['WhiteListUsers' => $Group->WhiteListUsers], ['Chat_id' => Get::$chat_id]);
                    Send::sendMessage(Get::$chat_id, 'کاربر با موفقیت به لیست سفید اضافه شد', null, false, false, Get::$message_id);
                } else {
                    Send::sendMessage(Get::$chat_id, 'کاربر از قبل در لیست وجود داشت', null, false, false, Get::$message_id);
                }
            } elseif (Get::$text == '!حذف از لیست سفید') {
                $new_list = [];
                foreach ($Group->WhiteListUsers as $WhiteListUser) {
                    if ($WhiteListUser != Get::$reply_to_from_id) {
                        $new_list[] = $WhiteListUser;
                    }
                }
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['WhiteListUsers' => $new_list], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'کاربر از لیست سفید حذف شد', null, false, false, Get::$message_id);
            } elseif (Get::$text == '!ریمو') {
                if (Get::$reply_to_from_id != ($id = explode(':', getenv('TOKEN'))[0]) && !in_array(Get::$reply_to_from_id, $sudors)) {
                    $res = Chat::kickChatMember(Get::$chat_id, Get::$reply_to_from_id);
                    if ($res->ok) {
                        Send::sendMessage(Get::$chat_id, 'کاربر ریمو شد', null, false, false, Get::$message_id);
                    } else {
                        Send::sendMessage(Get::$chat_id, 'کاربر به علت :'.PHP_EOL.$res->description.PHP_EOL.'ریمو نشد', null, false, false, Get::$message_id);
                    }
                } else {
                    Send::sendMessage(Get::$chat_id, 'امکان ریمو کردن ادمین وجود ندارد!', null, false, false, Get::$message_id);
                }
            } elseif (preg_match('~^!سکوت ([0-9]*)$~', Get::$text, $match)) {
                if (Get::$reply_to_from_id != ($id = explode(':', getenv('TOKEN'))[0]) && !in_array(Get::$reply_to_from_id, $sudors)) {
                    $res = Chat::restrictChatMember(Get::$chat_id, Get::$reply_to_from_id, ['can_send_messages' => false], $match[1]*60);
                    if ($res->ok) {
                        Send::sendMessage(Get::$chat_id, 'کاربر به مدت ' . $match[1] . ' دقیقه محدود شد', null, false, false, Get::$message_id);
                    } else {
                        Send::sendMessage(Get::$chat_id, 'کاربر به علت :'.PHP_EOL.$res->description.PHP_EOL.'محدود نشد', null, false, false, Get::$message_id);
                    }
                } else {
                    Send::sendMessage(Get::$chat_id, 'امکان محدود سازی ادمین وجود ندارد!', null, false, false, Get::$message_id);
                }
            }
        } else {
            if ((in_array($user_id, $sudors) || $user_id == $Group->Creator) && Get::$text == '!لیست ادمین ها') {
                $msg = 'لیست ادمین های گروه';
                foreach ($Group->Administrators as $admin) {
                    $msg .= PHP_EOL."<a href='tg://user?id=$admin'>$admin</a>";
                }
                Send::sendMessage(Get::$chat_id, $msg, 'HTML', false, false, null);
            } elseif (Get::$text == '!لیست سفید') {
                $msg = '<strong>'.'لیست سفید کاربران'.' : </strong>';
                foreach ($Group->WhiteListUsers as $WhiteListUser) {
                    $msg .= PHP_EOL."<a href='tg://user?id=$WhiteListUser'>$WhiteListUser</a>";
                }
                $msg .= PHP_EOL.PHP_EOL.'<strong>'.'لیست سفید کانال ها'.' : </strong>';
                foreach ($Group->WhiteListChannels as $WhiteListChannel) {
                    $msg .= PHP_EOL.'@'.$WhiteListChannel;
                }
                Send::sendMessage(Get::$chat_id, $msg, 'HTML', false, false, null);
            } elseif (Get::$text == '!قفل کردن گروه') {
                Send::sendMessage(Get::$chat_id, 'گروه قفل شد', 'HTML', false, false, null);
                $permission = [
                        'can_send_messages' => false,
                     ];
                Chat::setChatPermissions(Get::$chat_id, $permission);
            } elseif (Get::$text == '!باز کردن گروه') {
                Send::sendMessage(Get::$chat_id, 'گروه باز شد', 'HTML', false, false, null);
                $permission = [
                        'can_send_polls' => true,
                        'can_send_other_messages' => true,
                        'can_add_web_page_previews' => true,
                        'can_invite_users' => true,
                    ];
                Chat::setChatPermissions(Get::$chat_id, $permission);
            } elseif (Get::$text == '!تنظیمات'|| strpos(Get::$callback_query_data ?? 'Null', '/disableProperty_') !== false || strpos(Get::$callback_query_data ?? 'Null', '/enableProperty_') !== false) {
                if (strpos(Get::$callback_query_data ?? 'Null', '/disableProperty_') !== false) {
                    $Property = str_replace('/disableProperty_', '', Get::$callback_query_data);
                    $Group->Settings->$Property->Value = false;
                    $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['Settings' => $Group->Settings], ['Chat_id' => Get::$chat_id]);
                } elseif (strpos(Get::$callback_query_data ?? 'Null', '/enableProperty_') !== false) {
                    $Property = str_replace('/enableProperty_', '', Get::$callback_query_data);
                    $Group->Settings->$Property->Value = true;
                    $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['Settings' => $Group->Settings], ['Chat_id' => Get::$chat_id]);
                }
                $keys = [[]];
                foreach ($Group->Settings as $key => $value) {
                    if ($value->Value) {
                        $keys[][] = ['text' => $value->name . ' - ✅', 'callback_data' => '/disableProperty_' . $key];
                    } else {
                        $keys[][] = ['text' => $value->name . ' - ❌', 'callback_data' => '/enableProperty_' . $key];
                    }
                }
                $markup = Send::InlineKeyboardMarkup($keys);

                if (strpos(Get::$callback_query_data ?? 'Null', '/disableProperty_') !== false || strpos(Get::$callback_query_data ?? 'Null', '/enableProperty_') !== false) {
                    Edit::editMessageReplyMarkup(Get::$chat_id, Get::$message_id, null, $markup);
                } else {
                    Send::sendMessage(Get::$chat_id, 'لطفا برای فعال سازی یا غیر فعال سازی کلیک کنید', 'HTML', false, false, null, $markup);
                }
            } elseif (Get::$text == '!راهنما') {
                $msg = "<strong>دستورات عمومی :</strong> \nتنظیمات\nثبت ادمین (با ریپلای)\nحذف ادمین (با ریپلای)\nلیست ادمین ها\nریمو (با ریپلای)\nحذف 5 (حذف پیام های آخر بر حسب عدد)\nمیوت 5 (با ریپلای و بر حسب دقیقه)\nاخطار (با ریپلای)\nحذف اخطار (با ریپلای)\nقفل کردن گروه\nباز کردن گروه\n\n <strong>تغییر متن پیام‌های ربات :</strong> \nلیست پیام‌ ها\n\n <strong>فیلتر کلمات در گروه :</strong>\nفیلتر کلمه\nحذف فیلتر کلمه\nلیست فیلتر\n\n<strong>عضویت اجباری در کانال‌ها :</strong>\nافزودن کانال آیدی\nحذف کانال آیدی\nلیست کانال ها\n\n <strong>متغیرها :</strong> \n\n~FIRST_NAME~\n~LAST_NAME~\n~USERNAME~\n~USER_ID~\n~GROUP_ID~\n~GROUP_TITLE~\n~GROUP_USERNAME~\n~NEW_USER_ID~\n~NEW_LAST_NAME~\n~NEW_USERNAME~";
                Send::sendMessage(Get::$chat_id, $msg, 'HTML', false, false, null);
            } elseif (Get::$text == '!لیست پیام ها') {
                $msg = 'پیام ها :';
                foreach ($Group->MSGs as $MSG) {
                    $msg .= PHP_EOL.PHP_EOL.'<strong>'.$MSG->name.' : </strong>'.PHP_EOL.$MSG->MSG;
                }
                Send::sendMessage(Get::$chat_id, $msg, 'HTML', false, false, null);
            } elseif (Get::$text == '!لیست کانال ها') {
                $msg = 'کانال ها :';
                foreach ($Group->GPChannels as $GPChannel) {
                    $msg .= PHP_EOL.'@'.$GPChannel;
                }
                Send::sendMessage(Get::$chat_id, $msg, 'HTML', false, false, null);
            } elseif (Get::$text == '!لیست فیلتر') {
                $msg = 'کلمات فیلتر شده :';
                foreach ($Group->WordsFilterList as $Words) {
                    $msg .= PHP_EOL.$Words;
                }
                Send::sendMessage(Get::$chat_id, $msg, 'HTML', false, false, null);
            } elseif (preg_match('~^!افزودن به لیست سفید (.*)$~', Get::$text, $match)) {
                if (is_int($match[1])) {
                    if (!in_array($match[1], $Group->WhiteListUsers)) {
                        $Group->WhiteListUsers[] = $match[1];
                        $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['WhiteListUsers' => $Group->WhiteListUsers], ['Chat_id' => Get::$chat_id]);
                        Send::sendMessage(Get::$chat_id, 'کاربر با موفقیت به لیست سفید اضافه شد', null, false, false, Get::$message_id);
                    } else {
                        Send::sendMessage(Get::$chat_id, 'کاربر از قبل در لیست وجود داشت!', null, false, false, Get::$message_id);
                    }
                } elseif (substr($match[1], 0, 1) == '@') {
                    $Channel = trim($match[1], '@');
                    if (!in_array($Channel, $Group->WhiteListChannels)) {
                        $Group->WhiteListChannels[] = $Channel;
                        $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['WhiteListChannels' => $Group->WhiteListChannels], ['Chat_id' => Get::$chat_id]);
                        Send::sendMessage(Get::$chat_id, 'کانال با موفقیت به لیست سفید اضافه شد', null, false, false, Get::$message_id);
                    } else {
                        Send::sendMessage(Get::$chat_id, 'کانال از قبل در لیست وجود داشت!', null, false, false, Get::$message_id);
                    }
                } else {
                    Send::sendMessage(Get::$chat_id, 'فرمت ارسالی پشتیبانی نمی‌شود!', null, false, false, Get::$message_id);
                }
            } elseif (preg_match('~^!حذف از لیست سفید (.*)$~', Get::$text, $match)) {
                if (is_int($match[1])) {
                    $new_list = [];
                    foreach ($Group->WhiteListUsers as $WhiteListUser) {
                        if ($WhiteListUser != $match[1]) {
                            $new_list[] = $WhiteListUser;
                        }
                    }
                    $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['WhiteListUsers' => $new_list], ['Chat_id' => Get::$chat_id]);
                    Send::sendMessage(Get::$chat_id, 'کاربر از لیست سفید حذف شد', null, false, false, Get::$message_id);
                } elseif (substr($match[1], 0, 1) == '@') {
                    $new_list = [];
                    foreach ($Group->WhiteListChannels as $WhiteListChannel) {
                        if ($WhiteListChannel != trim($match[1], '@')) {
                            $new_list[] = $WhiteListChannel;
                        }
                    }
                    $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['WhiteListChannels' => $new_list], ['Chat_id' => Get::$chat_id]);
                    Send::sendMessage(Get::$chat_id, 'کانال از لیست سفید حذف شد', null, false, false, Get::$message_id);
                } else {
                    Send::sendMessage(Get::$chat_id, 'فرمت ارسال پشتیبانی نمی‌شود!', null, false, false, Get::$message_id);
                }
            } elseif (preg_match('~^!افزودن کانال (@.*)$~', Get::$text, $match)) {
                $Channel = trim($match[1], '@');
                if (!in_array($Channel, $Group->GPChannels)) {
                    $Group->GPChannels[] = $Channel;
                    $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['GPChannels' => $Group->GPChannels], ['Chat_id' => Get::$chat_id]);
                    Send::sendMessage(Get::$chat_id, 'کانال با موفقیت به لیست اضافه شد', null, false, false, Get::$message_id);
                } else {
                    Send::sendMessage(Get::$chat_id, 'کانال از قبل در لیست وجود داشت!', null, false, false, Get::$message_id);
                }
            } elseif (preg_match('~^!حذف کانال (@.*)$~', Get::$text, $match)) {
                $new_list = [];
                foreach ($Group->GPChannels as $GPChannels) {
                    if ($GPChannels != trim($match[1], '@')) {
                        $new_list[] = $GPChannels;
                    }
                }
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['GPChannels' => $new_list], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'کانال از لیست حذف شد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!فیلتر (.*)$~', Get::$text, $match)) {
                if (!in_array($match[1], $Group->WordsFilterList)) {
                    $Group->WordsFilterList[] = $match[1];
                    $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['WordsFilterList' => $Group->WordsFilterList], ['Chat_id' => Get::$chat_id]);
                    Send::sendMessage(Get::$chat_id, 'کلمه با موفقیت به لیست اضافه شد', null, false, false, Get::$message_id);
                } else {
                    Send::sendMessage(Get::$chat_id, 'کلمه از قبل در لیست وجود داشت!', null, false, false, Get::$message_id);
                }
            } elseif (preg_match('~^!حذف فیلتر (.*)$~', Get::$text, $match)) {
                $new_list = [];
                foreach ($Group->WordsFilterList as $Word) {
                    if ($Word != $match[1]) {
                        $new_list[] = $Word;
                    }
                }
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['WordsFilterList' => $new_list], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'کلمه از لیست حذف شد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!تغییر اد ([0-9]*)$~', Get::$text, $match)) {
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['AddNumber' => $match[1]], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'تعداد اد باموفقیت تغییر کرد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!حذف ادمین ([0-9]*)$~', Get::$text, $match)) {
                if (!in_array($match[1], $Group->WhiteListUsers)) {
                    $Group->WhiteListUsers[] = $match[1];
                    $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['WhiteListUsers' => $Group->WhiteListUsers], ['Chat_id' => Get::$chat_id]);
                    Send::sendMessage(Get::$chat_id, 'کاربر با موفقیت به لیست سفید اضافه شد', null, false, false, Get::$message_id);
                } else {
                    Send::sendMessage(Get::$chat_id, 'کاربر از قبل در لیست وجود داشت', null, false, false, Get::$message_id);
                }
            } elseif (preg_match('~^!ثبت ادمین ([0-9]*)$~', Get::$text, $match)) {
                $new_list = [];
                foreach ($Group->WhiteListUsers as $WhiteListUser) {
                    if ($WhiteListUser != $match[1]) {
                        $new_list[] = $WhiteListUser;
                    }
                }
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['WhiteListUsers' => $new_list], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'کاربر از لیست سفید حذف شد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!حذف ([0-9]*)$~', Get::$text, $match)) {
                if ($match[1] <= 20) {
                    $end = Get::$message_id - $match[1];
                    for ($i = Get::$message_id; $i >= $end; $i--) {
                        Chat::deleteMessage(Get::$chat_id, $i);
                    }
                } else {
                    Send::sendMessage(Get::$chat_id, 'در حال حاظر محدودیت 20 عددی وجود دارد.');
                }
            } elseif (preg_match('~^!تغییر پیام خوش آمدگویی (.*)~', Get::$text, $match)) {
                $Group->MSGs->WelcomeMSG->MSG = $match[1];
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['MSGs' => $Group->MSGs], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'پیام با موفقیت تغییر کرد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!تغییر پیام کپچا (.*)~', Get::$text, $match)) {
                $Group->MSGs->CaptchaMSG->MSG = $match[1];
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['MSGs' => $Group->MSGs], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'پیام با موفقیت تغییر کرد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!تغییر پیام عضویت اجباری (.*)~', Get::$text, $match)) {
                $Group->MSGs->ForceChannelJoinMSG->MSG = $match[1];
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['MSGs' => $Group->MSGs], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'پیام با موفقیت تغییر کرد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!تغییر پیام اد اجباری (.*)~', Get::$text, $match)) {
                $Group->MSGs->ForceADDMSG->MSG = $match[1];
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['MSGs' => $Group->MSGs], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'پیام با موفقیت تغییر کرد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!تغییر پیام گذارش اسپم (.*)~', Get::$text, $match)) {
                $Group->MSGs->SpamReportMSG->MSG = $match[1];
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['MSGs' => $Group->MSGs], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'پیام با موفقیت تغییر کرد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!تغییر پیام اخطار (.*)~', Get::$text, $match)) {
                $Group->MSGs->WarnMSG->MSG = $match[1];
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['MSGs' => $Group->MSGs], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'پیام با موفقیت تغییر کرد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!تغییر پیام اخطار اسپم (.*)~', Get::$text, $match)) {
                $Group->MSGs->BotWarnMSG->MSG = $match[1];
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['MSGs' => $Group->MSGs], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'پیام با موفقیت تغییر کرد', null, false, false, Get::$message_id);
            } elseif (preg_match('~^!تغییر پیام حذف اخطار (.*)~', Get::$text, $match)) {
                $Group->MSGs->delWarnMSG->MSG = $match[1];
                $DB->UpdateData('Groups/' . Get::$chat_id, Get::$chat_id, ['MSGs' => $Group->MSGs], ['Chat_id' => Get::$chat_id]);
                Send::sendMessage(Get::$chat_id, 'پیام با موفقیت تغییر کرد', null, false, false, Get::$message_id);
            }
        }
    }
    $DB->UpdateData('Users/', $user_id, ['MSGCount' => ++$User->MSGCount], ['User_id' => $user_id]);
    if (Get::$reply_to_from_id) {
        if (preg_match('~ممنون|مرسی|شکر|دمت~', Get::$text)) {
            if ($User->LTU + 60*10 < time() && Get::$reply_to_from_id != Get::$from_id) {
                $User = $DB->SelectData('Users', Get::$reply_to_from_id, ['User_id' => Get::$reply_to_from_id]);
                $DB->UpdateData('Users/', Get::$reply_to_from_id, ['ThankCount' => ++$User->ThankCount], ['User_id' => Get::$reply_to_from_id]);
            }
        } elseif ((Get::$text == 'فارسی') && ($text = Get::$reply_to_text ?? Get::$reply_to_caption) && isset($text)) {
            $msg = GoogleTranslate::translate('auto', 'fa', $text);
            Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
        } elseif ((Get::$text == 'انگلیسی') && ($text = Get::$reply_to_text ?? Get::$reply_to_caption) && isset($text)) {
            $msg = GoogleTranslate::translate('auto', 'en', $text);
            Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
        } elseif ((Get::$text == 'ایتالیایی') && ($text = Get::$reply_to_text ?? Get::$reply_to_caption) && isset($text)) {
            $msg = GoogleTranslate::translate('auto', 'it', $text);
            Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
        } elseif ((Get::$text == 'فرانسوی') && ($text = Get::$reply_to_text ?? Get::$reply_to_caption) && isset($text)) {
            $msg = GoogleTranslate::translate('auto', 'fr', $text);
            Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
        } elseif ((Get::$text == 'آلمانی') && ($text = Get::$reply_to_text ?? Get::$reply_to_caption) && isset($text)) {
            $msg = GoogleTranslate::translate('auto', 'de', $text);
            Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
        } elseif ((Get::$text == 'ترکی') && ($text = Get::$reply_to_text ?? Get::$reply_to_caption) && isset($text)) {
            $msg = GoogleTranslate::translate('auto', 'tr', $text);
            Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
        } elseif ((Get::$text == 'عربی') && ($text = Get::$reply_to_text ?? Get::$reply_to_caption) && isset($text)) {
            $msg = GoogleTranslate::translate('auto', 'ar', $text);
            Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
        } elseif ((Get::$text == 'ژاپنی') && ($text = Get::$reply_to_text ?? Get::$reply_to_caption) && isset($text)) {
            $msg = GoogleTranslate::translate('auto', 'ja', $text);
            Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
        } elseif ((Get::$text == 'چینی') && ($text = Get::$reply_to_text ?? Get::$reply_to_caption) && isset($text)) {
            $msg = GoogleTranslate::translate('auto', 'zh', $text);
            Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
        }
    } else {
        if ($Group->Settings->InfoMSGStatus->Value && (preg_match('~^(me|من|اینفو|info)$~i', Get::$text))) {
            $UserInfo = Get::getUserProfilePhotos(Get::$from_id);
            if ($UserInfo->ok) {
                if ($Member->ThankCount > 1000) {
                    $Position = '⭐⭐⭐⭐⭐';
                } elseif ($Member->ThankCount > 500) {
                    $Position = '⭐⭐⭐⭐';
                } elseif ($Member->ThankCount > 200) {
                    $Position = '⭐⭐⭐';
                } elseif ($Member->ThankCount > 100) {
                    $Position = '⭐⭐';
                } elseif ($Member->ThankCount < 10) {
                    $Position = '⭐';
                }
                $photo = end($UserInfo->result->photos[0])->file_id;
                $msg = 'نام : '.Get::$from_first_name.' '.Get::$from_last_name;
                if (Get::$from_username) {
                    $msg .="\n\n نام کاربری : ".Get::$from_username;
                }
                $msg .="\n\n یوزر آیدی : ".Get::$from_id."\n\n تعداد پیام های ارسالی در گروه‌ها : ".$User->MSGCount."\n\n تعداد تشکر ها : ".$User->ThankCount .PHP_EOL.PHP_EOL;
                if ($Member->WarnCount > 0) {
                    $msg .= "اخطارها : $Member->WarnCount \n \n ";
                }
                $msg .= "رتبه : $Position";
                if ($photo) {
                    Send::sendPhoto(Get::$chat_id, $photo, $msg, null, false, Get::$message_id);
                } else {
                    Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
                }
            }
        } elseif (preg_match('~امروز ?چن|^ساعت$|^تاریخ$|ساعت ?چن~', Get::$text)) {
            Send::sendMessage(Get::$chat_id, jdate("l, j F Y H:i:s", '', '', 'Asia/Tehran', 'en'), null, false, false, Get::$message_id);
        } elseif (strpos(Get::$callback_query_data ?? 'Null', '/captchaVerify_') !== false) {
            $explode_user_id = explode('_', Get::$callback_query_data)[1];
            if ($user_id == $explode_user_id) {
                Chat::deleteMessage(Get::$chat_id, Get::$message_id);
                $DB->UpdateData('Users', $user_id, ['CaptchaStatus' => true], ['User_id' => $user_id]);
            }
        } elseif ($Group->Settings->ConversationStatus->Value && in_array(Get::$text, array_keys(get_object_vars($words = $DB->SelectData('Bot', 'Words'))))) {
            $text = Get::$text;
            $msg = "اینم ".Get::$text." برای شما 😌 \n\n ".$words->$text;
            Send::sendMessage(Get::$chat_id, $msg, null, false, false, Get::$message_id);
        } elseif ($Group->Settings->ConversationStatus->Value && in_array(Get::$text, array_keys(get_object_vars($words = $DB->SelectData('Bot', 'conversation'))))) {
            $text = Get::$text;
            $rand_array = explode('&&&&', $words->$text);
            $num = sizeof($rand_array);
            Send::sendMessage(Get::$chat_id, $rand_array[rand(0, --$num)], null, false, false, Get::$message_id);
        }
    }
    if ($Group->Settings->DelTGServicesStatus->Value && (Get::$new_chat_member_id || Get::$left_chat_member_id || Get::$new_chat_title || Get::$new_chat_photo_file_id)) {
        Chat::deleteMessage(Get::$chat_id, Get::$message_id);
    }
    foreach ($DB->SelectData('Bot/', 'Temp') as $Temp) {
        if ($Temp->Type == 'Welcome' && $Temp->Time + $Group->DelWlcMSGAfter < time()) {
            $DB->DeleteData('Bot/', 'Temp', ['id' => $Temp->id]);
            Chat::deleteMessage($Temp->Chat_id, $Temp->Message_id);
        } elseif ($Temp->Type == 'ForceTOAddInGP' && $Temp->Time + $Group->DelForceAddMSGAfter < time()) {
            $DB->DeleteData('Bot/', 'Temp', ['id' => $Temp->id]);
            Chat::deleteMessage($Temp->Chat_id, $Temp->Message_id);
        } elseif ($Temp->Type == 'ForceTOChannelJoin' && $Temp->Time + $Group->DelForceJoinMSGAfter < time()) {
            $DB->DeleteData('Bot/', 'Temp', ['id' => $Temp->id]);
            Chat::deleteMessage($Temp->Chat_id, $Temp->Message_id);
        } elseif ($Temp->Type == 'Captcha' && $Temp->Time + $Group->DelCaptchaMSGAfter < time()) {
            $DB->DeleteData('Bot/', 'Temp', ['id' => $Temp->id]);
            Chat::deleteMessage($Temp->Chat_id, $Temp->Message_id);
        } elseif ($Temp->Type == 'Warn' && $Temp->Time + $Group->DelWarnMSGAfter < time()) {
            $DB->DeleteData('Bot/', 'Temp', ['id' => $Temp->id]);
            Chat::deleteMessage($Temp->Chat_id, $Temp->Message_id);
        } elseif ($Temp->Type == 'Report' && $Temp->Time + $Group->DelReportMSGAfter < time()) {
            $DB->DeleteData('Bot/', 'Temp', ['id' => $Temp->id]);
            Chat::deleteMessage($Temp->Chat_id, $Temp->Message_id);
        }
    }
}
