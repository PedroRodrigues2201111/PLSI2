<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\debug\panels\DumpPanel;
use yii\helpers\FileHelper;

/**
 * This is the model class for table "{{%products}}".
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $image
 * @property float $price
 * @property int $status
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class Product extends \yii\db\ActiveRecord
{
    /**
    * @var \yii\web\UploadedFile
    */
    public $imageFile;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%products}}';
    }

    public function behaviors()
    {
      return [
        TimestampBehavior::class,
        BlameableBehavior::class
      ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'price', 'status'], 'required'],
            [['description'], 'string'],
            [['price'], 'number'],
            [['imageFile'], 'image', 'extensions' => 'png, jpg, jpeg', 'maxSize' => 10 * 1024 * 1024], // Extensões de imagens para upload
            [['status', 'created_at', 'updated_at', 'created_by', 'updated_by'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['image'], 'string', 'max' => 2000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'image' => 'Imagem do Item',
            'imageFile' => 'Imagem do Item',
            'price' => 'Price',
            'status' => 'Published',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
        ];
    }

    /**
     * {@inheritdoc}
     * @return \common\models\query\ProductQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \common\models\query\ProductQuery(get_called_class());
    }

    public function save($runValidation = true, $attributeNames = null)
    {
      if ($this->imageFile){
          $this->image = '/products/'.Yii::$app->security->generateRandomString().'/'.$this->imageFile->name;
      }

      $transaction = Yii::$app->db->beginTransaction();
      $ok = parent::save($runValidation, $attributeNames);

      if($ok && $this->imageFile) {
          $fullPath = Yii::getAlias('@frontend/web/storage'.$this->image);
          $dir = dirname($fullPath);
          if (!FileHelper::createDirectory($dir) | !$this->imageFile->saveAs($fullPath)) {
              $transaction->rollBack();
              return false;
          }
      }
      $transaction->commit();
      return $ok;
    }

    public function getImageUrl()
    {
      if ($this->image) {
        return Yii::$app->params['frontendUrl'] . '/storage' . $this->image;
    }
      return Yii::$app->params['frontendUrl'] . '/img/no_image_available.png';
    }
}
