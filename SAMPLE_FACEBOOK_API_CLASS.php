<?php
/**
 * Copyright (c) 2015-present, Facebook, Inc. All rights reserved.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */

require __DIR__ . '/facebook/vendor/autoload.php';

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdsInsights;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\Ad;
use FacebookAds\Object\User;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Business;

class Facebook_Client{

  // Define required class properties
  private 
      $token,
      $CI,
      $accountID,
      $companyID,
      $agencyID,
      $dbname,
      $Agency_Manager,
      $redirectURI = array(
        "PLACEHOLDER_URI_1",
        "PLACEHOLDER_URI_2",
        "PLACEHOLDER_URI_3"
      ),
      $appid = 'APP_ID',
      $appsecret = 'APP_SECRET',
      $APIVersion = 'API_VERSION';

  public function __construct($init){
    if($init['dbname']) $this->dbname = $init['dbname'];
    if($init['token']) $this->token = $init['token'];
    if($init['accountID']) $this->accountID = $init['accountID'];
    if($init['companyID']) $this->companyID = $init['companyID'];
    if($init['agencyID']) $this->agencyID = $init['agencyID'];
    if($init['code']) $this->tempCode = $init['code'];;
    if($init['returnURI']) $this->returnURI = $init['returnURI'];
    $this->apptoken = "APP_TOKEN";

  }

  // Set User Token
  public function Set_Token($token){
    $this->token = $token;
  }

  // Set App ID
  public function Set_App_ID($appid){
    $this->appid = $appid;
  }

  // Set App Secret
  public function Set_App_Secret($appsecret){
    $this->appsecret = $appsecret;
  }

  // Set User Account ID
  public function Set_Account_ID($accountID){
    $this->accountID = $accountID;
  }

  // Set Internal Company ID
  public function Set_Company_ID($companyID){
    $this->companyID = $companyID;
  }

  // Set Internal Agency ID
  public function Set_Agency_ID($agencyID){
    $this->agencyID = $agencyID;
  }

  // Retrieve User Account ID
  public function Get_Account_ID(){
    return $this->accountID;
  }

  // Retrieve Internal Company ID
  public function Get_Company_ID(){
    return $this->companyID;
  }

  // Retrieve Internal Agency ID
  public function Get_Agency_ID(){
    return $this->agencyID;
  }

  // Retrieve App Token
  public function Get_App_Token(){
    return $this->apptoken;
  }

  // Set Business ID to corresponding internal agency in DB
  public function Set_Business_To_DB(){
    if($this->accountID && $this->agencyID && $this->dbname){
      $this->Agency_Manager = new Agency_Manager(array(
        'agency_id' => $this->agencyID
      ));
      $this->Agency_Manager->Set_Agency_Config_Update(array('facebook_business_id' => encrypt($this->accountID)));
      $this->Agency_Manager->Set_Unique_Name($this->dbname);
      return $this->Agency_Manager->Update_Agency_Config();
    }
    return array('success' => false, 'message' => 'Required parameters missing', 'errfile' => __DIR__.__FILE__);
  }

  // Set user token to corresponding internal agency in DB
  public function Set_Token_To_DB(){
    if($this->token && $this->agencyID && $this->dbname){
      $this->Agency_Manager = new Agency_Manager(array(
              'agency_id' => $this->agencyID
          ), TRUE);
          $this->Agency_Manager->Set_Agency_Config_Update(array('facebook_access_token' => encrypt($this->token),'facebook_app_secret' => encrypt($this->appsecret),'facebook_app_id' => encrypt($this->appid)));
          $this->Agency_Manager->Set_Unique_Name($this->dbname);
          return $this->Agency_Manager->Update_Agency_Config();
      }
      return array('success' => false, 'message' => 'Required parameters missing', 'errfile' => __DIR__.__FILE__);
  }

  // Set Account ID to corresponding internal company in DB
  public function Set_Account_ID_To_Company_DB(){
    $this->CI =& get_instance();
    return $this->CI->Socialads_model->insertSocialAdAccount($this->companyID, array(
            'facebook_adaccount' => encrypt($this->accountID)
          ));
  }

  // Associate FB campaigns to corresponding internal company in DB
  public function Set_Campaigns_To_Company_DB($data){
    $this->CI =& get_instance();
    return $this->CI->Socialads_model->insertSocialAccountCampaigns($data);
  }

  // Associate FB pixel to corresponding internal campaign in DB
  public function Insert_Campaign_Pixel($data, $data2){
    $this->CI =& get_instance();
    return $this->CI->Socialads_model->insertSocialCampaignPixel($data, $data2);
  }

  // Remove campaign data from corresponding internal campaign in DB
  public function Delete_Campaign($campaign){
    $this->CI =& get_instance();
    return $this->CI->Socialads_model->deleteSocialCampaign($campaign);
  }

  // Remove user token from corresponding internal agency in DB
  public function Unset_Token_From_DB(){
    if($this->agencyID && $this->dbname){
      $this->Agency_Manager = new Agency_Manager(array(
              'agency_id' => $this->agencyID
          ));
          $this->Agency_Manager->Set_Agency_Config_Update(array('facebook_access_token' => NULL,'facebook_app_secret' => NULL,'facebook_app_id' => NULL, 'facebook_business_id' => NULL));
          $this->Agency_Manager->Set_Unique_Name($this->dbname);
          return $this->Agency_Manager->Update_Agency_Config();
    }
    return array('success' => false, 'message' => 'Required parameters missing', 'errfile' => __DIR__.__FILE__);
  }

  // Disassociate campaigns from corresponding internal company in DB
  public function Unset_Campaigns_Company(){
    $this->CI =& get_instance();
    return $this->CI->Socialads_model->deleteSocialCampaigns($this->companyID);
  }

  // Disassociate account id from corresponding internal company in DB
  public function Unset_Account_ID_Company(){
    $this->CI =& get_instance();
    return $this->CI->Socialads_model->insertSocialAdAccount($this->companyID, array('facebook_adaccount'=>NULL));
  }

  /////// API CALLS START HERE ////////

  // Get account insights filtered by campaign
  public function Get_Social_Account_Insights_By_Campaign($ad_account_id, $campaign_filter = array(), $dates){

    $api = Api::init($this->appid, $this->appsecret, $this->token);

    $fields = array(
      'actions',
      'cost_per_action_type',
      'spend',
      'impressions',
      'frequency',
      'campaign_id',
      'campaign_name',
      'adset_id',
      'reach'
    );
    $params = array(
      'time_range' => '{"since":"'.$dates[0].'","until":"'.$dates[1].'"}',
      'filtering' => array(
        array(
          'field' => 'campaign.id',
          'operator' => 'IN',
          'value' => $campaign_filter
        )
      )
    );

    return json_decode(json_encode((new AdAccount($ad_account_id))->getInsights($fields,$params)->getResponse()->getContent()));
  }

  // List all account campaigns
  public function Get_Social_Account_Campaigns(){

    $ch = curl_init("https://graph.facebook.com/".$this->APIVersion."/".$this->accountID."/campaigns?limit=100000000&fields=name,id&access_token=".$this->token);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output1 = json_decode(curl_exec($ch));       
        curl_close($ch);

        return $output1;
  }

  // List all account pixels
  public function Get_Social_Account_Custom_Pixels(){ 
    $ch = curl_init("https://graph.facebook.com/".$this->APIVersion."/".$this->accountID."/customconversions?fields=name&limit=100000000&access_token=".$this->token);
          curl_setopt($ch, CURLOPT_HEADER, 0);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          $output = curl_exec($ch);       
          curl_close($ch);

          $standardConversions = array(
            array('id'=>'app_custom_event.fb_mobile_achievement_unlocked', 'name'=>'Mobile App Feature Unlocks'),
            array('id'=>'app_custom_event.fb_mobile_activate_app', 'name'=>'Mobile App Starts'),
            array('id'=>'app_custom_event.fb_mobile_add_payment_info', 'name'=>'Mobile App Payment Details'),
            array('id'=>'app_custom_event.fb_mobile_add_to_cart', 'name'=>'Mobile App Adds To Cart'),
            array('id'=>'app_custom_event.fb_mobile_add_to_wishlist', 'name'=>'Mobile App Adds to Wishlist'),
            array('id'=>'app_custom_event.fb_mobile_complete_registration', 'name'=>'Mobile App Registrations'),
            array('id'=>'app_custom_event.fb_mobile_content_view', 'name'=>'Mobile App Content Views'),
            array('id'=>'app_custom_event.fb_mobile_initiated_checkout', 'name'=>'Mobile App Checkouts'),
            array('id'=>'app_custom_event.fb_mobile_level_achieved', 'name'=>'Mobile App Achievements'),
            array('id'=>'app_custom_event.fb_mobile_purchase', 'name'=>'Mobile App Purchases'),
            array('id'=>'app_custom_event.fb_mobile_rate', 'name'=>'Mobile App Ratings'),
            array('id'=>'app_custom_event.fb_mobile_search', 'name'=>'Mobile App Searchs'),
            array('id'=>'app_custom_event.fb_mobile_spent_credits', 'name'=>'Mobile App Credit Spends'),
            array('id'=>'app_custom_event.fb_mobile_tutorial_completion', 'name'=>'Mobile App Tutorial Completions'),
            array('id'=>'app_custom_event.other', 'name'=>'Other Mobile App Actions'),
            array('id'=>'app_install', 'name'=>'App Installs'),
            array('id'=>'app_use', 'name'=>'App Uses'),
            array('id'=>'checkin', 'name'=>'Check-ins'),
            array('id'=>'comment', 'name'=>'Post Comments'),
            array('id'=>'commerce_event.add_to_cart', 'name'=>'Commerce Adds to Cart'),
            array('id'=>'commerce_event.message_to_buy', 'name'=>'Commerce Messages to Buy'),
            array('id'=>'commerce_event.purchase', 'name'=>'Commerce Purchases'),
            array('id'=>'commerce_event.view_content', 'name'=>'Commerce Product Views'),
            array('id'=>'credit_spent', 'name'=>'Credit Spends'),
            array('id'=>'games.plays', 'name'=>'Game Plays'),
            array('id'=>'interactive_component_tap', 'name'=>'Interactive Component Taps'),
            array('id'=>'landing_page_view', 'name'=>'Landing Page Views'),
            array('id'=>'leadgen.other', 'name'=>'Leads (Form)'),
            array('id'=>'like', 'name'=>'Page Likes'),
            array('id'=>'link_click', 'name'=>'Link Clicks'),
            array('id'=>'mobile_app_install', 'name'=>'Mobile App Installs'),
            array('id'=>'offsite_conversion.fb_pixel_add_payment_info', 'name'=>'Adds Payment Info'),
            array('id'=>'offsite_conversion.fb_pixel_add_to_cart', 'name'=>'Adds To Cart'),
            array('id'=>'offsite_conversion.fb_pixel_add_to_wishlist', 'name'=>'Adds To Wishlist'),
            array('id'=>'offsite_conversion.fb_pixel_complete_registration', 'name'=>'Completed Registration'),
            array('id'=>'offsite_conversion.fb_pixel_custom', 'name'=>'Custom pixel events defined by the advertiser'),
            array('id'=>'offsite_conversion.fb_pixel_initiate_checkout', 'name'=>'Initiates Checkout'),
            array('id'=>'offsite_conversion.fb_pixel_lead', 'name'=>'Leads'),
            array('id'=>'offsite_conversion.fb_pixel_purchase', 'name'=>'Purchases'),
            array('id'=>'offsite_conversion.fb_pixel_search', 'name'=>'Searchs'),
            array('id'=>'offsite_conversion.fb_pixel_view_content', 'name'=>'Views Content'),
            array('id'=>'onsite_conversion.flow_complete', 'name'=>'On-Facebook Workflow Completions'),
            array('id'=>'onsite_conversion.messaging_block', 'name'=>'Blocked Messaging Conversations'),
            array('id'=>'onsite_conversion.messaging_conversation_started_7d', 'name'=>'Messaging Conversations Started'),
            array('id'=>'onsite_conversion.messaging_first_reply', 'name'=>'New Messaging Conversations'),
            array('id'=>'onsite_conversion.messaging_reply', 'name'=>'Messaging Replies'),
            array('id'=>'onsite_conversion.purchase', 'name'=>'On-Facebook Purchases'),
            array('id'=>'photo_view', 'name'=>'Page Photo Views'),
            array('id'=>'post', 'name'=>'Post Shares'),
            array('id'=>'post_reaction', 'name'=>'Post Reactions'),
            array('id'=>'post_save', 'name'=>'Post Reactions'),
            array('id'=>'receive_offer', 'name'=>'Offer Saves'),
            array('id'=>'rsvp', 'name'=>'Event Responses'),
            array('id'=>'video_view', 'name'=>'3-Second Video Views'),
          );

          foreach((json_decode($output,true))['data'] as $pixel){
            $standardConversions[] = array(
              'name' => $pixel['name'],
              'id' => 'offsite_conversion.custom.'.$pixel['id']
            );
          }

          return json_encode(array('data'=>$standardConversions));
  }

  // List all business accounts we have access to
  public function Get_Facebook_Businesses(){
    $ch = curl_init("https://graph.facebook.com/".$this->APIVersion."/me/businesses?limit=100000000&fields=name,id&access_token=".$this->token);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = json_decode(curl_exec($ch));       
        curl_close($ch);

        return $output;
  }

  // List all accounts belonging to specified business account
  public function Get_Social_Accounts_List(){

    $ch = curl_init("https://graph.facebook.com/".$this->APIVersion."/".$this->accountID."/owned_ad_accounts?limit=100000000&fields=name,id&access_token=".$this->token);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output1 = json_decode(curl_exec($ch));       
        curl_close($ch);


    $ch = curl_init("https://graph.facebook.com/".$this->APIVersion."/".$this->accountID."/client_ad_accounts?limit=100000000&fields=name,id&access_token=".$this->token);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output2 = json_decode(curl_exec($ch));       
        curl_close($ch);

        return json_decode(json_encode((array_merge($output1->data,$output2->data))));
  }

  // List all campaign adsets
  public function Get_Social_Account_Campaign_AdSets($campaign_id, $dates){

    $ch = curl_init("https://graph.facebook.com/".$this->APIVersion."/".$campaign_id."/adsets?fields=id,name,status,insights.time_range({'since':'".$dates[0]."','until':'".$dates[1]."'}){actions,cost_per_action_type,spend,impressions,frequency,reach}&access_token=".$this->token);
          curl_setopt($ch, CURLOPT_HEADER, 0);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          $output = curl_exec($ch);       
          curl_close($ch);
          return json_decode($output);
  }

  // Get insights for specified campaign at the campaign level
  public function Get_Social_Account_Campaign_Insights($campaign_id, $dates){

    $ch = curl_init("https://graph.facebook.com/".$this->APIVersion."/".$campaign_id."/?fields=configured_status,insights.time_range({'since':'".$dates[0]."','until':'".$dates[1]."'}){actions,cost_per_action_type,spend,impressions,frequency,reach},name&access_token=".$this->token);
          curl_setopt($ch, CURLOPT_HEADER, 0);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          $output = curl_exec($ch);       
          curl_close($ch);
          return json_decode($output);
  }

  // Get insights at the adset level for specified campaign
  public function Get_Social_Account_Campaign_AdSet_Insights($adset_id, $dates){

    $ch = curl_init("https://graph.facebook.com/".$this->APIVersion."/".$adset_id."/?fields=configured_status,insights.time_range({'since':'".$dates[0]."','until':'".$dates[1]."'}){actions,cost_per_action_type,spend,impressions,frequency}&access_token=".$this->token);
          curl_setopt($ch, CURLOPT_HEADER, 0);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          $output = curl_exec($ch);       
          curl_close($ch);
          return $output;
  }

  // Redirect to the login dialog for oAuth auth
  public function APILogin(){
    header("Location: https://www.facebook.com/".$this->APIVersion."/dialog/oauth?client_id=".$this->appid."&redirect_uri=".$this->redirectURI[1]."&scope=email,ads_management&state=".json_encode(array('agencyID' => $this->agencyID, 'dbname' => $this->dbname, 'returnURI'=>$this->returnURI)));

  }

  // Get valid access token
  public function APIGetToken(){
    $ch = curl_init("https://graph.facebook.com/".$this->APIVersion."/oauth/access_token?client_id=".$this->appid."&redirect_uri=".$this->redirectURI[1]."&client_secret=".$this->appsecret."&code=".$this->tempCode);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);       
    curl_close($ch);
    return $output;
  }

  // Validate provided access token
  public function APIValidateToken(){
    $ch = curl_init("https://graph.facebook.com/debug_token?input_token=".$this->token."&access_token=".$this->apptoken->access_token);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);       
    curl_close($ch);
    return $output;
  }

  // Get app token
  public function APIGetAppToken(){
    header("Location: https://graph.facebook.com/oauth/access_token?client_id=".$this->appid."&client_secret=".$this->appsecret."&redirect_uri=".$this->redirectURI[2]."&grant_type=client_credentials");
  }

}

