<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/24/14
 * Time: 11:47 AM
 */
namespace yusufjonc07\miggen2\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Class AppAssets
 *
 * @package yusufjonc07\miggen2\assets
 */
class AppAssets extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public $sourcePath = '@vendor/yusufjonc07/yii2-miggen2/assets';

    /**
     * @inheritdoc
     */
    public $css = [
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'miggen2-migration.js',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\web\YiiAsset',
        'yii\widgets\ActiveFormAsset',
        'yii\bootstrap\BootstrapAsset',
    ];

    /**
     * @var array
     */
    public $jsOptions = [
        //'position' => View::POS_END,
    ];

}
