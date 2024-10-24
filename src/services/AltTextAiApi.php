<?php

namespace dispositiontools\craftalttextgenerator\services;

use Craft;
use craft\elements\Asset as AssetElement;
use craft\helpers\DateTimeHelper;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use dispositiontools\craftalttextgenerator\AltTextGenerator;
use dispositiontools\craftalttextgenerator\jobs\RefreshImageDetails as RefreshImageDetailsJob;
use dispositiontools\craftalttextgenerator\jobs\RequestAltText as RequestAltTextJob;
use dispositiontools\craftalttextgenerator\jobs\RequestHumanAltTextReview as RequestHumanAltTextReviewJob;
use dispositiontools\craftalttextgenerator\jobs\UpdateAssetWithGeneratedAltText as UpdateAssetWithGeneratedAltTextJob;

use dispositiontools\craftalttextgenerator\models\AltTextAiApiCall as AltTextAiApiCallModel;
use dispositiontools\craftalttextgenerator\records\AltTextAiApiCall as AltTextAiApiCallRecord;

use dispositiontools\craftalttextgenerator\models\QueueAllReport as QueueAllReportModel;

use yii\base\Component;
use Exception;

/**
 * Alt Text Ai Api service
 */
class AltTextAiApi extends Component
{
    /**
     * Stores apiCall to DB.
     *
     * @return model
     */
    public function saveApiCall(AltTextAiApiCallModel $model): AltTextAiApiCallModel
    {
        $isNew = !$model->id;
        
        if (!$isNew) {
            $record = AltTextAiApiCallRecord::findOne(['id' => $model->id]);
        } else {
            $record = new AltTextAiApiCallRecord();
        }
            
            
        $fieldsToUpdate = [
                'requestUserId',
                'assetId',
                'siteId',
                'requestType',
                'dateRequest',
                'request',
                'dateResponse',
                'response',
                'originalAltText',
                'generatedAltText',
                'altTextSyncStatus',
                'humanResponse',
                'humanDateResponse',
                'humanRequest',
                'humanDateRequest',
                'humanGeneratedAltText',
                'humanAltTextSyncStatus',
                'humanRequestUserId',
                'requestId',
            ];
            
        foreach ($fieldsToUpdate as $handle) {
            if (property_exists($model, $handle)) {
                $record->$handle = $model->$handle;
            }
        }
        
        $record->validate();
        $model->addErrors($record->getErrors());
        

        $record->save(false);

        if ($isNew) {
            $model->id = $record->id;
        }
        
        return  $model;
    }
    
    // AltTextGenerator::getInstance()->altTextAiApi->statsImagesWithAltText( );
    public function statsImagesWithAltText(): array
    {
        $assetsQuery = AssetElement::find()->kind('image')->hasAlt(false);
        $assets = $assetsQuery->all();
        $imagesWithoutAltText = 0;
        if(is_countable($assets ))
        {
            $imagesWithoutAltText = count($assets);
        }
        unset($assets);
        unset($assetsQuery);
        
       
       
        $assetsQuery = AssetElement::find()->kind('image')->hasAlt(true);
        $assets = $assetsQuery->all();
        $imagesWithAltText = 0;
        if(is_countable($assets ))
        {
            $imagesWithAltText = count($assets);
        }

        unset($assets);
        unset($assetsQuery);
        
        return [
           'imagesWithoutAltText' => $imagesWithoutAltText,
           'imagesWithAltText' => $imagesWithAltText,
        ];
    }
    
    
    /*
         Called from the review page in the CP
    */
    
    // AltTextGenerator::getInstance()->updateApiCalls( $assets, $altTextUpdates );
    public function updateApiCalls($assets, $altTextUpdates = null): ?bool
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        if ($currentUser) {
            $currentUserId = $currentUser->id;
        } else {
            $currentUserId = null;
        }
       
        if ($assets) {
            foreach ($assets as $apiCallId => $type) {
                switch ($type) {
                     
                     case "generatedSync":
                        
                              $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
                                if ($AltTextAiApiCallModel) {
                                    $AltTextAiApiCallModel->altTextSyncStatus = "syncing";

                                    if($altTextUpdates && isset($altTextUpdates['generatedAltText'][$apiCallId]) )
                                    {
                                        $AltTextAiApiCallModel->generatedAltText = $altTextUpdates['generatedAltText'][$apiCallId];
                                    }

                                    $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);
                                    Queue::push(new UpdateAssetWithGeneratedAltTextJob([
                                         "apiCallId" => $AltTextAiApiCallModel->id,
                                         "type" => "generated",
                                     ]));
                                     
                                    unset($AltTextAiApiCallModel);
                                }
                        
                        break;
                     
                     case "originalSync":
                        
                              $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
                                if ($AltTextAiApiCallModel) {
                                    Queue::push(new UpdateAssetWithGeneratedAltTextJob([
                                         "apiCallId" => $AltTextAiApiCallModel->id,
                                         "type" => "original",
                                     ]));
                                     
                                    unset($AltTextAiApiCallModel);
                                }
                     
                        break;
                     
                     case "humanGeneratedSync":
                        
                           $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
                             if ($AltTextAiApiCallModel) {
                                 $AltTextAiApiCallModel->altTextSyncStatus = "syncing";

                                 if($altTextUpdates && isset($altTextUpdates['humanGeneratedAltText'][$apiCallId]) )
                                 {
                                     $AltTextAiApiCallModel->humanGeneratedAltText = $altTextUpdates['humanGeneratedAltText'][$apiCallId];
                                 }

                                 $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);
                                 
                                 Queue::push(new UpdateAssetWithGeneratedAltTextJob([
                                      "apiCallId" => $AltTextAiApiCallModel->id,
                                      "type" => "humanGenerated",
                                  ]));
                                  
                                 unset($AltTextAiApiCallModel);
                             }
                           
                        
                             
                        break;
                        
                    case "requestRefresh":
                        
                                $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
                                 if ($AltTextAiApiCallModel) {
                                     $AltTextAiApiCallModel->altTextSyncStatus = "refreshing";
                                     $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);
                                     
                                     Queue::push(new RefreshImageDetailsJob([
                                          "apiCallId" => $AltTextAiApiCallModel->id,
                                          "humanRequestUserId" => $currentUserId,
                                      ]));
                                      
                                     unset($AltTextAiApiCallModel);
                                 }
                        
                        break;

                    case "requestResubmit":

                        $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
                                 if ($AltTextAiApiCallModel) {
                                     $AltTextAiApiCallModel->altTextSyncStatus = "resubmit";
                                     $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);
                                     
                                     Queue::push(new RequestAltTextJob([
                                          "assetId" => $AltTextAiApiCallModel->assetId,
                                          "overwrite" => true,
                                          "requestUserId" => $currentUserId,
                                          "actionType" => "Review"
                                      ]));
                                      
                                     unset($AltTextAiApiCallModel);
                                 }



                        break;
                        
                     case "requestHuman":
                        
                           $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
                             if ($AltTextAiApiCallModel) {
                                 Queue::push(new RequestHumanAltTextReviewJob([
                                      "apiCallId" => $AltTextAiApiCallModel->id,
                                      "humanRequestUserId" => $currentUserId,
                                  ]));
                                  
                                 unset($AltTextAiApiCallModel);
                             }
                           
                    
                        break;
                        
                     case "delete":
                           
                           $record = AltTextAiApiCallRecord::findOne(['id' => $apiCallId]);
                           $record->softDelete();
                           unset($record);
                     
                        break;
                        
                     case "cancel":
                        // don't do anything
                        break;
                     
                  }
            }
               
            // update the plugin badge count
            AltTextGenerator::getInstance()->altTextAiApi->refreshNumberOfAltTextsToReview();
        }
      
         
        return true;
    }


     /*
         Queue all imges for resyncing. 
    */
    
    // AltTextGenerator::getInstance()->queueAllImagesForResync();
    public function queueAllImagesForResync()
    {

        // select all api calls where the status is synced
        $recordsQuery = AltTextAiApiCallRecord::find();
      
        $recordsQuery->where(["altTextSyncStatus" => "synced"]);
        $recordsQuery->andWhere(["dateDeleted" => null]);
        $records = $recordsQuery->all();

        foreach ($records as $record) {
            $model = new AltTextAiApiCallModel($record->getAttributes());
            if( $model->generatedAltText != null) {
                Queue::push(new UpdateAssetWithGeneratedAltTextJob([
                    "apiCallId" => $model->id,
                    "type" => "generated",
                ]));
            }
            unset( $model);
        }
           
        return ["imagesQueue" => count($records)];
    
    }
    
    
    public function getAssetAndCheck($assetId)
    {
        // get the element
                
        $asset = AssetElement::find()->id($assetId)->one();
             
        if (!$asset) {
            return false;
        }
         
        $suitability = $this->checkAssetSuitability($asset);
           
        if (!$suitability['success']) {
            return false;
        }
           
        return  $asset;
    }
    
    
    // AltTextGenerator::getInstance()->refreshImageDetailsFromAltTextAi( $apiCallId );
    public function refreshImageDetailsFromAltTextAi($apiCallId, $requestUserId = false)
    {
        $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
        
        if (!$AltTextAiApiCallModel) {
            return false;
        }
        
        
        $requestId = $AltTextAiApiCallModel->requestId;
        if (!$requestId) {
            $requestId = $AltTextAiApiCallModel->id;
        }
        
        $imageDetails = $this->makeGetImageByAssetIdApiCall($requestId);

        $logMessage = "Refreshing image response: ".$apiCallId;
        AltTextGenerator::info($logMessage);
        AltTextGenerator::info($imageDetails);
        
        if (!$imageDetails) {
            return false;
        }
        
        $imageDetailsArray = json_decode($imageDetails, true);
        if (!$imageDetailsArray) {
            return false;
        }
        
        $asset = AssetElement::find()->id($AltTextAiApiCallModel->assetId)->one();
              
        if (!$asset) {
            return false;
        }
        
        $updateModel = false;
        if ($imageDetailsArray['alt_text']) {
            if (
                $AltTextAiApiCallModel->humanDateRequest
                && $imageDetailsArray['alt_text'] != $AltTextAiApiCallModel->generatedAltText
                && $imageDetailsArray['alt_text'] != $AltTextAiApiCallModel->humanGeneratedAltText
            ) {
                $AltTextAiApiCallModel->humanGeneratedAltText = $imageDetailsArray['alt_text'];
                $AltTextAiApiCallModel->humanAltTextSyncStatus = "review";
                $AltTextAiApiCallModel->altTextSyncStatus = "review";
                $updateModel = true;
            } elseif ($imageDetailsArray['alt_text'] != $AltTextAiApiCallModel->generatedAltText) {
                $AltTextAiApiCallModel->generatedAltText = $imageDetailsArray['alt_text'];
                $AltTextAiApiCallModel->altTextSyncStatus = "review";
                $updateModel = true;
            }
            
            if ($updateModel) {
                $this->saveApiCall($AltTextAiApiCallModel);
            }
        }
        
        
        
        
        return true;
    }
    
    
    public function updateAssetWithGeneratedAltText($apiCallId, $type = "generated")
    {
        if (!$apiCallId) {
            return false;
        }
        $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
       
        if (!$AltTextAiApiCallModel) {
            return false;
        }
        
        if (!$AltTextAiApiCallModel->assetId) {
            return false;
        }
        $settings = AltTextGenerator::getInstance()->getSettings();
      
        // get Asset
        $asset = $this->getAssetAndCheck($AltTextAiApiCallModel->assetId);
        if (!$asset) {
            return false;
        }
      
        switch ($type) {
               case "generated":
                  
                     if ($AltTextAiApiCallModel->generatedAltText != "") {

                         if(isset($settings->customField) && ( $settings->customField == "alt" || $settings->customField == null ))
                         {
                             $asset->alt = $AltTextAiApiCallModel->generatedAltText;
                         }
                         elseif( $asset->getFieldLayout()->getFieldByHandle($settings->customField) )
                         {
                             $asset->setFieldValue($settings->customField, $AltTextAiApiCallModel->generatedAltText );
                         }
                         else{
                             $asset->alt = $AltTextAiApiCallModel->generatedAltText;
                         }

                         $success = Craft::$app->elements->saveElement($asset);
                         $AltTextAiApiCallModel->altTextSyncStatus = "synced";
                         $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);
                     }
                  
                  break;
                  
                  
               case "original":
               
                  if ($AltTextAiApiCallModel->originalAltText != "") {
                
                      if(isset($settings->customField) && ( $settings->customField == "alt" || $settings->customField == null ))
                         {
                             $asset->alt = $AltTextAiApiCallModel->originalAltText;
                         }
                         elseif( $asset->getFieldLayout()->getFieldByHandle($settings->customField) )
                         {
                             $asset->setFieldValue($settings->customField, $AltTextAiApiCallModel->originalAltText );
                         }
                         else{
                             $asset->alt = $AltTextAiApiCallModel->originalAltText;
                         }

                      $success = Craft::$app->elements->saveElement($asset);
                  }
               
                  break;
                  
               case "humanGenerated":
               
                  if ($AltTextAiApiCallModel->humanGeneratedAltText != "") {

                      if(isset($settings->customField) && ( $settings->customField == "alt" || $settings->customField == null ))
                         {
                             $asset->alt = $AltTextAiApiCallModel->humanGeneratedAltText;
                         }
                         elseif( $asset->getFieldLayout()->getFieldByHandle($settings->customField) )
                         {
                             $asset->setFieldValue($settings->customField, $AltTextAiApiCallModel->humanGeneratedAltText );
                         }
                         else{
                             $asset->alt = $AltTextAiApiCallModel->humanGeneratedAltText;
                         }
                      $success = Craft::$app->elements->saveElement($asset);
                      $AltTextAiApiCallModel->altTextSyncStatus = "synced";
                      $AltTextAiApiCallModel->humanGeneratedAltText = "synced";
                      $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);
                  }
               
                  break;
         }
      
        
        
        return true;
    }
    
    // AltTextGenerator::getInstance()->altTextAiApi->getApiCalls( );
    public function getApiCalls($criteria = null): array
    {
        $recordsQuery = AltTextAiApiCallRecord::find();
      
        if (array_key_exists('where', $criteria)) {
            $x = 0;
    
            foreach ($criteria['where'] as $criteriaItem => $criteriaValue) {
                $x++;
                if ($x == 1) {
                    $recordsQuery->where([$criteriaItem => $criteriaValue]);
                } else {
                    $recordsQuery->andWhere([$criteriaItem => $criteriaValue]);
                }
            }
            
            $recordsQuery->andWhere(["dateDeleted" => null]);
        }
         
        $records = $recordsQuery->all();
        
        $models = array();
       
        foreach ($records as $record) {
            $model = new AltTextAiApiCallModel($record->getAttributes());
            $models[] = $model;
        }
       
        return $models;
    }
    
    public function getApiCallById($id): ?AltTextAiApiCallModel
    {
        $record = AltTextAiApiCallRecord::findOne(['id' => $id]);
        
        if (!$record) {
            return null;
        }
        
        $model = new AltTextAiApiCallModel($record->getAttributes());
        return $model;
    }
    
    
    public function getApiCallByAssetId($id): ?AltTextAiApiCallModel
    {
        $record = AltTextAiApiCallRecord::findOne(['assetId' => $id]);
        
        if (!$record) {
            return null;
        }
        
        $model = new AltTextAiApiCallModel($record->getAttributes());
        return $model;
    }
    
    
    // AltTextGenerator::getInstance()->altTextAiApi->checkGetApiCallById( $id );
    public function checkGetApiCallById($id)
    {
        $model = $this->getApiCallById($id);
        
        print_r($model);
    }
    
    
    public function checkAssetSuitability($asset): array
    {
        
        $settings = AltTextGenerator::getInstance()->getSettings();

        // is the element type webp / png / jpg / BMP
        if (!$asset->kind == "image") {
            $logMessage = "Asset Id: ".$asset->id. "Not an image";
            AltTextGenerator::info($logMessage);
            return [
                'error' => true,
                'errorMessage' => "Not an image",
                'success' => false,
            ];
        }
        
        
        if (!in_array(strtolower($asset->extension), ['jpg', 'gif', 'png', 'webp', 'jpeg'])) {
            $logMessage = "Asset Id: ".$asset->id. "Not right kind of image: " .$asset->extension;
            AltTextGenerator::info($logMessage);
            return [
                'error' => true,
                'errorMessage' => "Not right kind of image: " .$asset->extension,
                'success' => false,
            ];
        }
        
        
        // check if the image is less than 10mb
          
        if (
                ( $settings->useImagePreviewUrl == "never" || $settings->useImagePreviewUrl == null || $settings->useImagePreviewUrl == false)
                && $asset->size > 10000000
            ) {
                $logMessage = "Asset Id: ".$asset->id. "Image file size is over 10mb ";
                AltTextGenerator::info($logMessage);
            return [
                'error' => true,
                'errorMessage' => "Image file size is over 10mb",
                'success' => false,
            ];
        }
        
        // check if the image is over 50 x 50
        if ($asset->width < 51 || $asset->height < 51) {
            $logMessage = "Asset Id: ".$asset->id. "Image too small ";
                AltTextGenerator::info($logMessage);
            return [
                'error' => true,
                'errorMessage' => "Image needs to over 50 x 50 pixels",
                'success' => false,
            ];
        }
        
        
        return [
            'error' => false,
            'errorMessage' => "",
            'success' => true,
        ];
    }
    
    
    public function callAltTextAiHumanReviewAipi($apiCallId, $humanRequestUserId = null)
    {
        // get apiCall model
        //
        
        $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
        
        if (!$AltTextAiApiCallModel) {
            //echo "no model";
            return true;
        }
        
        $asset = AssetElement::find()->id($AltTextAiApiCallModel->assetId)->one();
              
        if (!$asset) {
            $return = [
                  'error' => true,
                  'errorMessage' => "No asset",
              ];
            return $return;
        }
          
        $suitability = $this->checkAssetSuitability($asset);
         
        if (!$suitability['success']) {
            return $suitability;
        }
        
        $AltTextAiApiCallModel->humanRequestUserId = $humanRequestUserId;
        $AltTextAiApiCallModel->humanAltTextSyncStatus = "requesting";
        $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);
        
        $requestId = $AltTextAiApiCallModel->requestId;
        if (!$requestId) {
            $requestId = $AltTextAiApiCallModel->id;
        }
        
        $resultsJson = $this->requestHumanReviewApiCall($requestId);
    

        if ($resultsJson) {
            $AltTextAiApiCallModel->humanAltTextSyncStatus = "requested";
            $AltTextAiApiCallModel->humanRequest = json_encode($resultsJson);
            $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);
        }
    }
    
    
    // AltTextGenerator::getInstance()->altTextAiApi->callAltTextAiAipi( $assetId );
    public function callAltTextAiAipi($assetId, $requestType = "No type", $async = false, $requestUserId = false, $overwrite = false)
    {

        // see if the asset has already been called
        // we are not allowed to recall it. so we will set it to review again
        $settings = AltTextGenerator::getInstance()->getSettings();
        $AltTextAiApiCallModel = $this->getApiCallByAssetId($assetId);

        if ($AltTextAiApiCallModel && $overwrite === false) {
            $AltTextAiApiCallModel->altTextSyncStatus = "refreshing";
            $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);

            Queue::push(new RefreshImageDetailsJob([
                  "apiCallId" => $AltTextAiApiCallModel->id,
                  "humanRequestUserId" => $requestUserId,
              ]));
            return true;
        }
        elseif($AltTextAiApiCallModel && $overwrite)
        {
            //
        }
        else {
            $AltTextAiApiCallModel = new AltTextAiApiCallModel();
        }


        // get the element
        $asset = AssetElement::find()->id($assetId)->one();

        if (!$asset) {
            return [
                'error' => true,
                'errorMessage' => "No asset",
            ];
        }

        $suitability = $this->checkAssetSuitability($asset);

        if (!$suitability['success']) {
            return $suitability;
        }

        // check if we have enough credits
        // if we don't have credits pause this...


        // create an rquestId

        $requestId = StringHelper::toKebabCase(Craft::$app->getSystemName()) . "_" . $asset->uid . "_" . $asset->id;

        // create a call model

        if (  $settings->useImagePreviewUrl == "always" 
                || ( $settings->useImagePreviewUrl == "forLargeImages" && $asset->size > 10000000)
        ) {
            // use image preview url if it's been set in settings
             $assetUrl = Craft::$app->getAssets()->getImagePreviewUrl($asset, 2000, 2000);
        }else{
            $assetUrl = $asset->url;
        }

     

        $AltTextAiApiCallModel->assetId = $asset->id;
        $AltTextAiApiCallModel->requestId = $requestId;
        $AltTextAiApiCallModel->requestType = $requestType;
        $AltTextAiApiCallModel->dateRequest = DateTimeHelper::currentUTCDateTime();
        if($overwrite)
        {
            $AltTextAiApiCallModel->altTextSyncStatus = "recalled";
        }
        else{
            $AltTextAiApiCallModel->altTextSyncStatus = "called";
        }   
        
     

        if(isset($settings->customField) && ( $settings->customField == "alt" || $settings->customField == null ))
        {
            $AltTextAiApiCallModel->originalAltText = $asset->alt;
        }
        elseif( $asset->getFieldLayout()->getFieldByHandle($settings->customField) )
        {
            $AltTextAiApiCallModel->originalAltText =  $asset->getFieldValue($settings->customField );
        }
        else{
            $AltTextAiApiCallModel->originalAltText = $asset->alt;
        }

        if ($requestUserId) {
            $AltTextAiApiCallModel->requestUserId = $requestUserId;
        }

        // do the call

        $settings = AltTextGenerator::getInstance()->getSettings();

        $modelName = $settings->modelName;
        if($modelName == "" || $modelName == null)
        {
            $modelName = "describe-regular";
        }

        $lang = $settings->lang;
        if($lang == "" || $lang == null)
        {
            $lang = "en";
        }

        $webHookParams = [
            'securityCode' => $settings->securityCode,
        ];
        $webhookUrl = UrlHelper::actionUrl('alt-text-generator/alt-text-ai-webhook/web-hook', $webHookParams, null, false);
        $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);

        $imageUrl = UrlHelper::siteUrl($assetUrl);

        $callDetails = [
            "image" => [
                "url" => $imageUrl,
                "asset_id" => $AltTextAiApiCallModel->requestId,
                "metadata" => [
                   "assetId" => $asset->id,
                   "apiCallId" => $AltTextAiApiCallModel->id,
                ],
            ],
            "model_name" => $modelName,
            "async" => (bool) $settings->asyncApi,
            "lang" => $lang
        ];

        if ($settings->asyncApi) {
            $callDetails['webhook_url'] = $webhookUrl;
        }

        if( $overwrite ){
            $callDetails['overwrite'] = true;
        }

        $AltTextAiApiCallModel->request = json_encode($callDetails);
        $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);


        $resultsJson = $this->makeCreateImageApiCall($callDetails);
        AltTextGenerator::info($resultsJson);
        $AltTextAiApiCallModel->response = $resultsJson;
        $AltTextAiApiCallModel->dateResponse = DateTimeHelper::currentUTCDateTime();
        $AltTextAiApiCallModel->altTextSyncStatus = "received";
        $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);

        $resultsArray = json_decode($resultsJson, true);
       

        if($resultsArray && array_key_exists('errors', $resultsArray ) && count($resultsArray['errors']) > 0)
        {
            $AltTextAiApiCallModel->altTextSyncStatus = "errors";
            $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);
        }

        elseif (!$async && $resultsArray && array_key_exists('alt_text', $resultsArray )) {

            $newAltText = $resultsArray['alt_text'];
            $AltTextAiApiCallModel->generatedAltText = $newAltText;

            if ($settings->useAltTextImmediately) {


                if(isset($settings->customField) && ( $settings->customField == "alt" || $settings->customField == null ))
                {
                    $asset->alt = $newAltText;
                }
                elseif( $asset->getFieldLayout()->getFieldByHandle($settings->customField) )
                {
                    $asset->setFieldValue($settings->customField, $newAltText );
                }
                else{
                    $asset->alt = $newAltText;
                }

                $success = Craft::$app->elements->saveElement($asset);
                $AltTextAiApiCallModel->altTextSyncStatus = "synced";
            } else {
                $AltTextAiApiCallModel->altTextSyncStatus = "review";
            }

            $AltTextAiApiCallModel = $this->saveApiCall($AltTextAiApiCallModel);
        }




        // what is they get a 429 error


        // what if it already exists?
        // then we need to update the asset straight away
    }
    
    
    
    
    // AltTextGenerator::getInstance()->altTextAiApi->processAltTextAiWebhook( $hookData );
    public function processAltTextAiWebhook($hookData)
    {
        // this saves the hook data to the api call hook
        $settings = AltTextGenerator::getInstance()->getSettings();
        
        $responseArray = json_decode($hookData, true);
        
      
        if ($responseArray && array_key_exists("event", $responseArray)) {
            if ($responseArray['event'] == "uploaded") {
                $this->processAltTextAiWebhookUploaded($hookData);
            }
            
            if ($responseArray['event'] == "reviewed") {
                $this->processAltTextAiWebhookReviewed($hookData);
            }
        }
        unset($hookData);
        unset($responseArray);
    }
    
    
    public function processAltTextAiWebhookUploaded($hookData)
    {
        $responseArray = json_decode($hookData, true);
        
        $settings = AltTextGenerator::getInstance()->getSettings();
        
        if (is_array($responseArray) && array_key_exists("data", $responseArray) && array_key_exists("images", $responseArray['data'])) {
            foreach ($responseArray['data']['images'] as $imageResponse) {
                if (array_key_exists('asset_id',  $imageResponse)) {
                    $apiCallId = $imageResponse['asset_id'];
                    if (array_key_exists('metadata',  $imageResponse) && array_key_exists('apiCallId',  $imageResponse['metadata'])) {
                        $apiCallId = $imageResponse['metadata']['apiCallId'];
                    }
                       
                    $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
                       
                    if ($AltTextAiApiCallModel) {
                        $AltTextAiApiCallModel->dateResponse = DateTimeHelper::currentUTCDateTime();
                        $AltTextAiApiCallModel->response = $hookData;
                           
                        $AltTextAiApiCallModel->generatedAltText = $imageResponse['alt_text'];
                           
                        if ($settings->useAltTextImmediately === true) {
                            $asset = AssetElement::find()->id($AltTextAiApiCallModel->assetId)->one();
                            if ($asset) {
                        

                                if(isset($settings->customField) && ( $settings->customField == "alt" || $settings->customField == null ))
                                {
                                    $asset->alt = $imageResponse['alt_text'];
                                }
                                elseif( $asset->getFieldLayout()->getFieldByHandle($settings->customField) )
                                {
                                    $asset->setFieldValue($settings->customField, $imageResponse['alt_text'] );
                                }
                                else{
                                    $asset->alt = $imageResponse['alt_text'];
                                }

                                
                                $success = Craft::$app->elements->saveElement($asset);
                                $AltTextAiApiCallModel->altTextSyncStatus = "synced";
                                
                                unset($asset);
                            } else {
                                $AltTextAiApiCallModel->altTextSyncStatus = "review";
                            }
                        } else {
                            $AltTextAiApiCallModel->altTextSyncStatus = "review";
                        }
                           
                           
                        $this->saveApiCall($AltTextAiApiCallModel);
                        unset($AltTextAiApiCallModel);
                    }
                }
            }
        }
        
       
        unset($responseArray);
    }
    
    
    public function processAltTextAiWebhookReviewed($hookData)
    {
        $responseArray = json_decode($hookData, true);
        $settings = AltTextGenerator::getInstance()->getSettings();
        
        if (is_array($responseArray) && array_key_exists("data", $responseArray) && array_key_exists("images", $responseArray['data'])) {
            foreach ($responseArray['data']['images'] as $imageResponse) {
                if (array_key_exists('asset_id',  $imageResponse)) {
                    $apiCallId = $imageResponse['asset_id'];
                    if (array_key_exists('metadata',  $imageResponse) && array_key_exists('apiCallId',  $imageResponse['metadata'])) {
                        $apiCallId = $imageResponse['metadata']['apiCallId'];
                    }
                       
                    $AltTextAiApiCallModel = $this->getApiCallById($apiCallId);
                       
                    if ($AltTextAiApiCallModel) {
                        $AltTextAiApiCallModel->humanDateResponse = DateTimeHelper::currentUTCDateTime();
                        $AltTextAiApiCallModel->humanResponse = $hookData;
                           
                        $AltTextAiApiCallModel->humanGeneratedAltText = $imageResponse['alt_text'];
                           
                        if ($settings->useAltTextImmediately) {
                            $asset = AssetElement::find()->id($AltTextAiApiCallModel->assetId)->one();
                            if ($asset) {

                                if(isset($settings->customField) && ( $settings->customField == "alt" || $settings->customField == null ))
                                {
                                    $asset->alt = $imageResponse['alt_text'];
                                }
                                elseif( $asset->getFieldLayout()->getFieldByHandle($settings->customField) )
                                {
                                    $asset->setFieldValue($settings->customField,$imageResponse['alt_text'] );
                                }
                                else{
                                    $asset->alt = $imageResponse['alt_text'];
                                }
                                
                                $success = Craft::$app->elements->saveElement($asset);
                                $AltTextAiApiCallModel->altTextSyncStatus = "synced";
                                $AltTextAiApiCallModel->humanAltTextSyncStatus = "synced";
                                unset($asset);
                            } else {
                                $AltTextAiApiCallModel->altTextSyncStatus = "review";
                                $AltTextAiApiCallModel->humanAltTextSyncStatus = "review";
                            }
                        } else {
                            $AltTextAiApiCallModel->altTextSyncStatus = "review";
                            $AltTextAiApiCallModel->humanAltTextSyncStatus = "review";
                        }
                           
                           
                        $this->saveApiCall($AltTextAiApiCallModel);
                        unset($AltTextAiApiCallModel);
                    }
                }
            }
        }
        
        unset($responseArray);
    }
    
    
    
    
    public function getAltTextForUrl($url)
    {
        $callDetails = [
            "image" => [
                "url" => $url,
            ],
        ];
        $altTextApiResponse = $this->makeCreateImageApiCall($callDetails);
        
        return  $altTextApiResponse;
    }
    
    
  
    
   
    
    
    // AltTextGenerator::getInstance()->altTextAiApi->queueAllImages($generateForNoAltText, $generateForAltText, $overwrite);
    public function queueAllImages($generateForNoAltText = false , $generateForAltText = false, $overwrite = false)
    {
        $websiteUrl = rtrim(UrlHelper::baseSiteUrl(), "/");
                
        $settings = AltTextGenerator::getInstance()->getSettings();
        $customField = false;
        if(isset($settings->customField))
        {
            $customField = $settings->customField;
        }
        
        $currentUser = Craft::$app->getUser()->getIdentity();
        if ($currentUser) {
            $currentUserId = $currentUser->id;
        } else {
            $currentUserId = null;
        }

        $queueAllReport  = new QueueAllReportModel();
        
        $numberOfCredits = AltTextGenerator::getInstance()->altTextAiApi->getNumberOfAltTextApiCredits();

        $queueAllReport->numberOfCredits = $numberOfCredits;
        $requestCount = 0;
        $numberRejected = 0;
        $numberRequested = 0;
        
        if ($generateForNoAltText) {


            if( $customField && ( $settings->customField == "alt" || $settings->customField == null ))
            {
                $assetsQuery = AssetElement::find()->kind('image')->hasAlt(false);
            }
            else
            {
                try
                {
                    $assetsQuery = AssetElement::find()->kind('image')->$customField(':empty:');
                }
                catch (Exception $e)
                {
                    $assetsQuery = AssetElement::find()->kind('image')->hasAlt(false);
                }   
            }

            
           



            $assets = $assetsQuery->all();
            $numberOfAssets = count($assets);
            $queueAllReport->numberOfAssetsWithNoAltText = count($assets);
            foreach ($assets as $asset) {
                
                
                if ($requestCount >= $numberOfCredits) {
                    $numberRejected++;

                    $queueAllReport->numberOfAssetsRejectedWithNoAltText++;
                    $queueAllReport->assets[] = [
                        "assetId" => $asset->id,
                        "assetUrl" => $asset->url,
                        "assetTitle" => $asset->title,
                        "assetQueueStatus" => "Not queued due to lack of credits"
                    ];
                    continue;
                }
                
                
                $suitability = $this->checkAssetSuitability($asset);
                

                if (!$suitability['success']) {
                    $numberRejected++;
                    $queueAllReport->numberOfAssetsRejectedWithNoAltText++;
                    $queueAllReport->assets[] = [
                        "assetId" => $asset->id,
                        "assetUrl" => $asset->url,
                        "assetTitle" => $asset->title,
                        "assetQueueStatus" =>  $suitability['errorMessage']
                    ];
                    continue;
                }
                
                $numberRequested++;

                $queueAllReport->numberOfAssetsQueuedWithNoAltText++;
                $queueAllReport->assets[] = [
                    "assetId" => $asset->id,
                    "assetUrl" => $asset->url,
                    "assetTitle" => $asset->title,
                    "assetQueueStatus" => "Queued"
                ];
                Queue::push(new RequestAltTextJob([
                    "assetId" => $asset->id,
                    "requestUserId" => $currentUserId,
                    "actionType" => "Queue all",
                    "overwrite" => $overwrite
                ]));
                $requestCount++;
                unset($suitability);
            }
            unset($assets);
        }
        
        if ($generateForAltText) {
            $assetsQuery = AssetElement::find()->kind('image')->hasAlt(true);


    

            if( $customField && ( $settings->customField == "alt" || $settings->customField == null ))
            {
                $assetsQuery = AssetElement::find()->kind('image')->hasAlt(true);
            }
            else
            {
                try
                {
                    $assetsQuery = AssetElement::find()->kind('image')->$customField(':notempty:');
                }
                catch (Exception $e)
                {
                    $assetsQuery = AssetElement::find()->kind('image')->hasAlt(true);
                }   
            }



            $assets = $assetsQuery->all();
            $queueAllReport->numberOfAssetsWithAltText = count($assets);
            foreach ($assets as $asset) {
                
                
                if ($requestCount >= $numberOfCredits) {
                    $numberRejected++;
                    $queueAllReport->numberOfAssetsRejectedWithAltText++;


                    $queueAllReport->assets[] = [
                        "assetId" => $asset->id,
                        "assetUrl" => $asset->url,
                        "assetTitle" => $asset->title,
                        "assetQueueStatus" =>  "Not queued due to lack of credits"
                    ];
                    continue;
                }
                
                $suitability = $this->checkAssetSuitability($asset);
                
                if (!$suitability['success']) {
                    $numberRejected++;
                    $queueAllReport->numberOfAssetsRejectedWithAltText++;

                    $queueAllReport->assets[] = [
                        "assetId" => $asset->id,
                        "assetUrl" => $asset->url,
                        "assetTitle" => $asset->title,
                        "assetQueueStatus" =>  $suitability['errorMessage']
                    ];
                    continue;
                }
                $numberRequested ++;
                $queueAllReport->numberOfAssetsQueuedWithAltText++;
                $queueAllReport->assets[$asset->id] = "Queued";
                Queue::push(new RequestAltTextJob([
                    "assetId" => $asset->id,
                    "requestUserId" => $currentUserId,
                    "actionType" => "Queue all",
                    "overwrite" => $overwrite
                ]));
                $requestCount++;
                unset($suitability);
            }
            unset($assets);
        }
        
        
        /*
        echo $websiteUrl ;
        echo "\n";
        echo "\n";
        */
        
        return $queueAllReport;
    }
    
    
    
    public function makeGetImageByAssetIdApiCall($asset_id)
    {
        $url = "https://alttext.ai/api/v1/images/" . $asset_id;
    
        $settings = AltTextGenerator::getInstance()->getSettings();
        $apiKey = $settings->getApiKey(true);
        $options = array(
          'http' => array(
            'method' => "GET",
            'header' => "X-API-Key: " . $apiKey . "\n" .
                      "Content-Type: application/json",
            "ignore_errors" => true,
          ),
        );
        
        $context = stream_context_create($options);
        $jsonResponse = file_get_contents($url, false, $context);
        
        return $jsonResponse;
    }
    
    public function makeCreateImageApiCall($callDetailsArray)
    {
        $callDetailsJson = json_encode($callDetailsArray);
        $url = "https://alttext.ai/api/v1/images";

        $settings = AltTextGenerator::getInstance()->getSettings();
        $apiKey = $settings->getApiKey(true);
        $options = array(
          'http' => array(
            'method' => "POST",
            'header' => "X-API-Key: " . $apiKey . "\n" .
                      "Content-Type: application/json",
            'content' => $callDetailsJson,
            "ignore_errors" => true,
          ),
        );
        
        $context = stream_context_create($options);
        $jsonResponse = @file_get_contents($url, false, $context);

        // Check if response is false (indicating an error)
        if ($jsonResponse === false) {
            // Parse the HTTP response headers to get the status code
            if (isset($http_response_header)) {
                // Extract the HTTP status code from the response headers
                $status_line = $http_response_header[0];
                $jsonResponse = '{"errors": "'.$status_line.'" }';
            } else {
                $jsonResponse = '{"errors":"HTTP request failed: No headers returned." }';
            }
        } 
        
        return $jsonResponse;
    }
    
    
    public function requestHumanReviewApiCall($asset_id)
    {
        $url = "https://alttext.ai/api/v1/images/" . $asset_id . "/augment";
    
        $settings = AltTextGenerator::getInstance()->getSettings();
        $apiKey = $settings->getApiKey(true);
        $options = array(
           'http' => array(
             'method' => "POST",
             'header' => "X-API-Key: " . $apiKey . "\n" .
                       "Content-Type: application/json",
             "ignore_errors" => true,
           ),
         );
         
        $context = stream_context_create($options);
        $jsonResponse = file_get_contents($url, false, $context);
        
         
        return $jsonResponse;
    }
    
    // AltTextGenerator::getInstance()->altTextAiApi->makeAccountApiCall();
   
    public function makeAccountApiCall()
    {
        $url = "https://alttext.ai/api/v1/account";
       
        $settings = AltTextGenerator::getInstance()->getSettings();
        $apiKey = $settings->getApiKey(true);

        if (!$apiKey) {
            return false;
        }
       
        $options = array(
         'http' => array(
           'method' => "GET",
           'header' => "X-API-Key:  " . $apiKey . "\n",
           'ignore_errors' => true
         ),
       );
       
        $context = stream_context_create($options);
        $jsonResponse = @file_get_contents($url, false, $context);
        if($jsonResponse )
        {
            return $jsonResponse;
        }
        else{
            return false;
        }
        
    }


    public function getApikeyStatus($accountJson)
    {
        $accountArray = json_decode($accountJson, true);


        if ( $accountArray && array_key_exists('name',$accountArray)) {
            Craft::$app->cache->set('altTextApiName', $accountArray['name'], 60 * 500);
            if ( array_key_exists('subscription',$accountArray) && is_array($accountArray['subscription']) && array_key_exists('status',$accountArray['subscription']) )
            {
                if($accountArray['subscription']['status'] == "active"){
                    Craft::$app->cache->set('altTextApiError', false, 60 * 500);
                }
                else{
                    Craft::$app->cache->set('altTextApiError', 'API key error', 60 * 500);
                   
                }
                Craft::$app->cache->set('altTextApiStatus', $accountArray['subscription']['status'], 60 * 500);
            }
            else{
                Craft::$app->cache->set('altTextApiStatus', "No active subscription (Free trial or Pay as you go)", 60 * 500);
            }
            
        }
        else{
            Craft::$app->cache->set('altTextApiError', 'API key error', 60 * 500);
            Craft::$app->cache->set('altTextApiName', false, 60 * 500);
            Craft::$app->cache->set('altTextApiStatus', false, 60 * 500);
        }

    }
    
    // AltTextGenerator::getInstance()->altTextAiApi->getNumberOfAltTextApiCredits();
    public function getNumberOfAltTextApiCredits()
    {
        $creditsCacheKey = "altTextApiCreditsCount";
        
        // Get the cached value, but if it doesn't exist, re-do the work
        // and store it for 60 seconds
        $credits = Craft::$app->cache->getOrSet($creditsCacheKey, function() {
            // Some expensive work goes in here
            
            return $this->refreshNumberOfAltTextApiCredits();
        }, 60 * 10);
        
        return $credits;
    }
    
    // AltTextGenerator::getInstance()->altTextAiApi->getNumberOfAltTextsToReview();
    public function getNumberOfAltTextsToReview()
    {
        $altTextNumberOfItemsToReview = Craft::$app->cache->getOrSet("altTextNumberOfItemsToReview", function() {
            // Some expensive work goes in here
             
            $reviewCalls = AltTextGenerator::getInstance()->altTextAiApi->getApiCalls([
                 'where' =>
                 [
                     'altTextSyncStatus' => ['review'],
                 ],
             ]);
             
            $numberOfItemsToReview = 0;
            if (is_countable($reviewCalls)) {
                $numberOfItemsToReview = count($reviewCalls);
            }
             
            return $numberOfItemsToReview;
        }, 60 * 5);
         
        return  $altTextNumberOfItemsToReview;
    }
    
    // AltTextGenerator::getInstance()->altTextAiApi->refreshNumberOfAltTextsToReview();
    public function refreshNumberOfAltTextsToReview(): int
    {
        $reviewCalls = AltTextGenerator::getInstance()->altTextAiApi->getApiCalls([
            'where' =>
            [
                'altTextSyncStatus' => ['review'],
            ],
        ]);
        
        $numberOfItemsToReview = 0;
        if (is_countable($reviewCalls)) {
            $numberOfItemsToReview = count($reviewCalls);
        }
        
        Craft::$app->cache->set("altTextNumberOfItemsToReview", $numberOfItemsToReview, 60 * 60);
        return $numberOfItemsToReview;
    }
    
    
    // AltTextGenerator::getInstance()->altTextAiApi->refreshNumberOfAltTextApiCredits();
    public function refreshNumberOfAltTextApiCredits()
    {
        $accountJson = $this->makeAccountApiCall();

        $this->getApikeyStatus($accountJson);
        
        $accountArray = json_decode($accountJson, true);
        $usage = 0;
        $usage_limit = 0;

        if ($accountArray) {
            if (array_key_exists('usage', $accountArray)) {
                $usage = $accountArray['usage'];
            }
            
            if (array_key_exists('usage_limit', $accountArray)) {
                $usage_limit = $accountArray['usage_limit'];
            }

        }
       
        
        
        $credits = $usage_limit - $usage;
        
        $creditsCacheKey = "altTextApiCreditsCount";
        
        Craft::$app->cache->set($creditsCacheKey, $credits, 60 * 15);
        
        return $credits;
    }
}
