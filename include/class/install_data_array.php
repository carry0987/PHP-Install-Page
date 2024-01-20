<?php
if (defined('IN_INSTALL') !== true) exit('Access Denied');

$insert = array();
$insert[0] = 0;
$insert[1] = 1;
//Founder
$insert['group'] = 1;

//Insert default value
$user_query = 'INSERT INTO user (username, password, group_id, language, last_login, join_date) VALUES (:username, :password, :group_id, :language, :last_login, :join_date)';
