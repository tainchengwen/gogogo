<?php

namespace App\Jobs;

use App\PrintSequeue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;

class sendLabelByMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mail;
    protected $arr;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($mail,$arr)
    {
        //
        $this -> mail = $mail;
        $this -> arr = implode(',',$arr);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        //制作pdf
        $html_url = url('mpLabelPage').'?ids='.$this -> arr;
        $pdf = PrintSequeue::printHtml($html_url,1);
        Log::info($pdf);


        $mail = new PHPMailer();
        // 是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式

        $mail->SMTPDebug = 1;

// 使用smtp鉴权方式发送邮件

        $mail->isSMTP();

// smtp需要鉴权 这个必须是true

        $mail->SMTPAuth = true;

// 链接qq域名邮箱的服务器地址

        //$mail->Host = 'smtp.qq.com';
        $mail->Host = 'smtp.exmail.qq.com';

// 设置使用ssl加密方式登录鉴权

        $mail->SMTPSecure = 'ssl';

// 设置ssl连接smtp服务器的远程服务器端口号

        $mail->Port = 465;

// 设置发送的邮件的编码

        $mail->CharSet = 'UTF-8';

// 设置发件人昵称 显示在收件人邮件的发件人邮箱地址前的发件人姓名

        $mail->FromName = '寰球优品生活物流';

// smtp登录的账号 QQ邮箱即可

        //$mail->Username = '1021019025@qq.com';
        $mail->Username = 'it_admin@fenith.com';

// smtp登录的密码 使用生成的授权码

        //$mail->Password = 'ttzfydwhpquzbcbi';
        $mail->Password = 'Suzhou456';

// 设置发件人邮箱地址 同登录账号

        //$mail->From = '1021019025@qq.com';
        $mail->From = 'it_admin@fenith.com';

// 邮件正文是否为html编码 注意此处是一个方法

        $mail->isHTML(true);

// 设置收件人邮箱地址

        $mail->addAddress('1127129489@qq.com');

// 添加多个收件人 则多次调用方法即可

        $mail->addAddress(trim($this -> mail));

// 添加该邮件的主题

        $mail->Subject = '寰球优品生活物流包裹编号';

        $html = "包裹编号见附件";
        $mail->Body = $html;

// 为该邮件添加附件

        $mail->addAttachment($pdf,'寰球优品生活物流包裹编号.pdf');

// 发送邮件 返回状态

        $state = $mail->send();



        Log::info('发送邮件结果');
        Log::info($state);
    }
}
