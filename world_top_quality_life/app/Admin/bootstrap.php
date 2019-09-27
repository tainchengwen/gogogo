<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */
use Encore\Admin\Grid\Column;
use App\Admin\Extensions\Popover;
use App\Admin\Extensions\TrackAlert;
use Encore\Admin\Facades\Admin;

Column::extend('popover', Popover::class);
Column::extend('trackalert',TrackAlert::class);

Encore\Admin\Form::forget(['map', 'editor']);
Admin::js(asset('js/laydate/laydate.js'));
Admin::js(asset('js/layer/2.2/layer.js'));

