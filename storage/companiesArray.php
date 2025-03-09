<?php
//
//$companies = [
//    "102845072" => [146, 153, 219, 220, 221, 226, 228],   // 220
//    "103597021" => [102, 107], // 107
//    "121848087" => [40, 235], // 235
//    "147186242" => [205, 225, 227], // 227
//    "175258695" => [186, 208], // 208
//    "200615942" => [88, 89], // 89
//    "200752332" => [79, 85],
//    "200854216" => [196, 263],
//    "202787969" => [224, 245],
//    "202922281" => [160, 237],
//    "203040817" => [35, 252],
//    "203046923" => [190, 272],
//    "203471785" => [65, 108, 114],
//    "203605798" => [44, 273],
//    "204126475" => [75, 76],
//    "204315694" => [51, 52],
//    "206397144" => [29, 73],
//    "207187526" => [238, 290],
//    "825326392" => [24, 151],
//    "832048438" => [63, 81],
//];
//
//
//UPDATE `company_files` SET `company_id` = 220 WHERE `company_id` IN (146, 153, 219, 220, 221, 226, 228);
//UPDATE `company_categories` SET `company_id` = 220 WHERE `company_id` IN (146, 153, 219, 220, 221, 226, 228);
//UPDATE `candidates` SET `company_id` = 220 WHERE `company_id` IN (146, 153, 219, 220, 221, 226, 228);
//UPDATE `company_adresses` SET `company_id` = 220 WHERE `company_id` IN (146, 153, 219, 220, 221, 226, 228);
//UPDATE `arrivals` SET `company_id` = 220 WHERE `company_id` IN (146, 153, 219, 220, 221, 226, 228);
//UPDATE `company_jobs` SET `company_id` = 220 WHERE `company_id` IN (146, 153, 219, 220, 221, 226, 228);
//UPDATE `user_owners` SET `company_id` = 220 WHERE `company_id` IN (146, 153, 219, 220, 221, 226, 228);
//DELETE FROM `companies` WHERE `id` IN (146, 153, 219, 221, 226, 228);
//
//UPDATE `company_files` SET `company_id` = 107 WHERE `company_id` IN (102, 107);
//UPDATE `company_categories` SET `company_id` = 107 WHERE `company_id` IN (102, 107);
//UPDATE `candidates` SET `company_id` = 107 WHERE `company_id` IN (102, 107);
//UPDATE `company_adresses` SET `company_id` = 107 WHERE `company_id` IN (102, 107);
//UPDATE `arrivals` SET `company_id` = 107 WHERE `company_id` IN (102, 107);
//UPDATE `company_jobs` SET `company_id` = 107 WHERE `company_id` IN (102, 107);
//UPDATE `user_owners` SET `company_id` = 107 WHERE `company_id` IN (102, 107);
//DELETE FROM `companies` WHERE `id` = 102;
//
//UPDATE `company_files` SET `company_id` = 235 WHERE `company_id` IN (40, 235);
//UPDATE `company_categories` SET `company_id` = 235 WHERE `company_id` IN (40, 235);
//UPDATE `candidates` SET `company_id` = 235 WHERE `company_id` IN (40, 235);
//UPDATE `company_adresses` SET `company_id` = 235 WHERE `company_id` IN (40, 235);
//UPDATE `arrivals` SET `company_id` = 235 WHERE `company_id` IN (40, 235);
//UPDATE `company_jobs` SET `company_id` = 235 WHERE `company_id` IN (40, 235);
//UPDATE `user_owners` SET `company_id` = 235 WHERE `company_id` IN (40, 235);
//DELETE FROM `companies` WHERE `id` = 40;
//
//UPDATE `company_files` SET `company_id` = 227 WHERE `company_id` IN (205, 225, 227);
//UPDATE `company_categories` SET `company_id` = 227 WHERE `company_id` IN (205, 225, 227);
//UPDATE `candidates` SET `company_id` = 227 WHERE `company_id` IN (205, 225, 227);
//UPDATE `company_adresses` SET `company_id` = 227 WHERE `company_id` IN (205, 225, 227);
//UPDATE `arrivals` SET `company_id` = 227 WHERE `company_id` IN (205, 225, 227);
//UPDATE `company_jobs` SET `company_id` = 227 WHERE `company_id` IN (205, 225, 227);
//UPDATE `user_owners` SET `company_id` = 227 WHERE `company_id` IN (205, 225, 227);
//DELETE FROM `companies` WHERE `id` IN (205, 225);
//
//UPDATE `company_files` SET `company_id` = 208 WHERE `company_id` IN (186, 208);
//UPDATE `company_categories` SET `company_id` = 208 WHERE `company_id` IN (186, 208);
//UPDATE `candidates` SET `company_id` = 208 WHERE `company_id` IN (186, 208);
//UPDATE `company_adresses` SET `company_id` = 208 WHERE `company_id` IN (186, 208);
//UPDATE `arrivals` SET `company_id` = 208 WHERE `company_id` IN (186, 208);
//UPDATE `company_jobs` SET `company_id` = 208 WHERE `company_id` IN (186, 208);
//UPDATE `user_owners` SET `company_id` = 208 WHERE `company_id` IN (186, 208);
//DELETE FROM `companies` WHERE `id` = 186;
//
//UPDATE `company_files` SET `company_id` = 89 WHERE `company_id` IN (88, 89);
//UPDATE `company_categories` SET `company_id` = 89 WHERE `company_id` IN (88, 89);
//UPDATE `candidates` SET `company_id` = 89 WHERE `company_id` IN (88, 89);
//UPDATE `company_adresses` SET `company_id` = 89 WHERE `company_id` IN (88, 89);
//UPDATE `arrivals` SET `company_id` = 89 WHERE `company_id` IN (88, 89);
//UPDATE `company_jobs` SET `company_id` = 89 WHERE `company_id` IN (88, 89);
//UPDATE `user_owners` SET `company_id` = 89 WHERE `company_id` IN (88, 89);
//DELETE FROM `companies` WHERE `id` = 88;
//
//UPDATE `company_files` SET `company_id` = 85 WHERE `company_id` IN (79, 85);
//UPDATE `company_categories` SET `company_id` = 85 WHERE `company_id` IN (79, 85);
//UPDATE `candidates` SET `company_id` = 85 WHERE `company_id` IN (79, 85);
//UPDATE `company_adresses` SET `company_id` = 85 WHERE `company_id` IN (79, 85);
//UPDATE `arrivals` SET `company_id` = 85 WHERE `company_id` IN (79, 85);
//UPDATE `company_jobs` SET `company_id` = 85 WHERE `company_id` IN (79, 85);
//UPDATE `user_owners` SET `company_id` = 85 WHERE `company_id` IN (79, 85);
//DELETE FROM `companies` WHERE `id` = 79;
//
//UPDATE `company_files` SET `company_id` = 263 WHERE `company_id` IN (196, 263);
//UPDATE `company_categories` SET `company_id` = 263 WHERE `company_id` IN (196, 263);
//UPDATE `candidates` SET `company_id` = 263 WHERE `company_id` IN (196, 263);
//UPDATE `company_adresses` SET `company_id` = 263 WHERE `company_id` IN (196, 263);
//UPDATE `arrivals` SET `company_id` = 263 WHERE `company_id` IN (196, 263);
//UPDATE `company_jobs` SET `company_id` = 263 WHERE `company_id` IN (196, 263);
//UPDATE `user_owners` SET `company_id` = 263 WHERE `company_id` IN (196, 263);
//UPDATE `users` SET `company_id` = 263 WHERE `company_id` IN (196,263);
//DELETE FROM `companies` WHERE `id` = 196;
//
//UPDATE `company_files` SET `company_id` = 245 WHERE `company_id` IN (224, 245);
//UPDATE `company_categories` SET `company_id` = 245 WHERE `company_id` IN (224, 245);
//UPDATE `candidates` SET `company_id` = 245 WHERE `company_id` IN (224, 245);
//UPDATE `company_adresses` SET `company_id` = 245 WHERE `company_id` IN (224, 245);
//UPDATE `arrivals` SET `company_id` = 245 WHERE `company_id` IN (224, 245);
//UPDATE `company_jobs` SET `company_id` = 245 WHERE `company_id` IN (224, 245);
//UPDATE `user_owners` SET `company_id` = 245 WHERE `company_id` IN (224, 245);
//UPDATE `users` SET `company_id` = 245 WHERE `company_id` IN (224,245);
//DELETE FROM `companies` WHERE `id` = 224;
//
//UPDATE `company_files` SET `company_id` = 237 WHERE `company_id` IN (160, 237);
//UPDATE `company_categories` SET `company_id` = 237 WHERE `company_id` IN (160, 237);
//UPDATE `candidates` SET `company_id` = 237 WHERE `company_id` IN (160, 237);
//UPDATE `company_adresses` SET `company_id` = 237 WHERE `company_id` IN (160, 237);
//UPDATE `arrivals` SET `company_id` = 237 WHERE `company_id` IN (160, 237);
//UPDATE `company_jobs` SET `company_id` = 237 WHERE `company_id` IN (160, 237);
//UPDATE `user_owners` SET `company_id` = 237 WHERE `company_id` IN (160, 237);
//DELETE FROM `companies` WHERE `id` = 160;
//
//UPDATE `company_files` SET `company_id` = 252 WHERE `company_id` IN (35, 252);
//UPDATE `company_categories` SET `company_id` = 252 WHERE `company_id` IN (35, 252);
//UPDATE `candidates` SET `company_id` = 252 WHERE `company_id` IN (35, 252);
//UPDATE `company_adresses` SET `company_id` = 252 WHERE `company_id` IN (35, 252);
//UPDATE `arrivals` SET `company_id` = 252 WHERE `company_id` IN (35, 252);
//UPDATE `company_jobs` SET `company_id` = 252 WHERE `company_id` IN (35, 252);
//UPDATE `user_owners` SET `company_id` = 252 WHERE `company_id` IN (35, 252);
//DELETE FROM `companies` WHERE `id` = 35;
//
//UPDATE `company_files` SET `company_id` = 272 WHERE `company_id` IN (190, 272);
//UPDATE `company_categories` SET `company_id` = 272 WHERE `company_id` IN (190, 272);
//UPDATE `candidates` SET `company_id` = 272 WHERE `company_id` IN (190, 272);
//UPDATE `company_adresses` SET `company_id` = 272 WHERE `company_id` IN (190, 272);
//UPDATE `arrivals` SET `company_id` = 272 WHERE `company_id` IN (190, 272);
//UPDATE `company_jobs` SET `company_id` = 272 WHERE `company_id` IN (190, 272);
//UPDATE `user_owners` SET `company_id` = 272 WHERE `company_id` IN (190, 272);
//DELETE FROM `companies` WHERE `id` = 190;
//
//UPDATE `company_files` SET `company_id` = 114 WHERE `company_id` IN (65, 108, 114);
//UPDATE `company_categories` SET `company_id` = 114 WHERE `company_id` IN (65, 108, 114);
//UPDATE `candidates` SET `company_id` = 114 WHERE `company_id` IN (65, 108, 114);
//UPDATE `company_adresses` SET `company_id` = 114 WHERE `company_id` IN (65, 108, 114);
//UPDATE `arrivals` SET `company_id` = 114 WHERE `company_id` IN (65, 108, 114);
//UPDATE `company_jobs` SET `company_id` = 114 WHERE `company_id` IN (65, 108, 114);
//UPDATE `user_owners` SET `company_id` = 114 WHERE `company_id` IN (65, 108, 114);
//DELETE FROM `companies` WHERE `id` IN (65, 108);
//
//UPDATE `company_files` SET `company_id` = 273 WHERE `company_id` IN (44, 273);
//UPDATE `company_categories` SET `company_id` = 273 WHERE `company_id` IN (44, 273);
//UPDATE `candidates` SET `company_id` = 273 WHERE `company_id` IN (44, 273);
//UPDATE `company_adresses` SET `company_id` = 273 WHERE `company_id` IN (44, 273);
//UPDATE `arrivals` SET `company_id` = 273 WHERE `company_id` IN (44, 273);
//UPDATE `company_jobs` SET `company_id` = 273 WHERE `company_id` IN (44, 273);
//UPDATE `user_owners` SET `company_id` = 273 WHERE `company_id` IN (44, 273);
//DELETE FROM `companies` WHERE `id` = 44;
//
//UPDATE `company_files` SET `company_id` = 76 WHERE `company_id` IN (75, 76);
//UPDATE `company_categories` SET `company_id` = 76 WHERE `company_id` IN (75, 76);
//UPDATE `candidates` SET `company_id` = 76 WHERE `company_id` IN (75, 76);
//UPDATE `company_adresses` SET `company_id` = 76 WHERE `company_id` IN (75, 76);
//UPDATE `arrivals` SET `company_id` = 76 WHERE `company_id` IN (75, 76);
//UPDATE `company_jobs` SET `company_id` = 76 WHERE `company_id` IN (75, 76);
//UPDATE `user_owners` SET `company_id` = 76 WHERE `company_id` IN (75, 76);
//DELETE FROM `companies` WHERE `id` = 75;
//
//UPDATE `company_files` SET `company_id` = 52 WHERE `company_id` IN (51, 52);
//UPDATE `company_categories` SET `company_id` = 52 WHERE `company_id` IN (51, 52);
//UPDATE `candidates` SET `company_id` = 52 WHERE `company_id` IN (51, 52);
//UPDATE `company_adresses` SET `company_id` = 52 WHERE `company_id` IN (51, 52);
//UPDATE `arrivals` SET `company_id` = 52 WHERE `company_id` IN (51, 52);
//UPDATE `company_jobs` SET `company_id` = 52 WHERE `company_id` IN (51, 52);
//UPDATE `user_owners` SET `company_id` = 52 WHERE `company_id` IN (51, 52);
//DELETE FROM `companies` WHERE `id` = 51;
//
//UPDATE `company_files` SET `company_id` = 73 WHERE `company_id` IN (29, 73);
//UPDATE `company_categories` SET `company_id` = 73 WHERE `company_id` IN (29, 73);
//UPDATE `candidates` SET `company_id` = 73 WHERE `company_id` IN (29, 73);
//UPDATE `company_adresses` SET `company_id` = 73 WHERE `company_id` IN (29, 73);
//UPDATE `arrivals` SET `company_id` = 73 WHERE `company_id` IN (29, 73);
//UPDATE `company_jobs` SET `company_id` = 73 WHERE `company_id` IN (29, 73);
//UPDATE `user_owners` SET `company_id` = 73 WHERE `company_id` IN (29, 73);
//DELETE FROM `companies` WHERE `id` = 29;
//
//UPDATE `company_files` SET `company_id` = 290 WHERE `company_id` IN (238, 290);
//UPDATE `company_categories` SET `company_id` = 290 WHERE `company_id` IN (238, 290);
//UPDATE `candidates` SET `company_id` = 290 WHERE `company_id` IN (238, 290);
//UPDATE `company_adresses` SET `company_id` = 290 WHERE `company_id` IN (238, 290);
//UPDATE `arrivals` SET `company_id` = 290 WHERE `company_id` IN (238, 290);
//UPDATE `company_jobs` SET `company_id` = 290 WHERE `company_id` IN (238, 290);
//UPDATE `user_owners` SET `company_id` = 290 WHERE `company_id` IN (238, 290);
//DELETE FROM `companies` WHERE `id` = 238;
//
//UPDATE `company_files` SET `company_id` = 151 WHERE `company_id` IN (24, 151);
//UPDATE `company_categories` SET `company_id` = 151 WHERE `company_id` IN (24, 151);
//UPDATE `candidates` SET `company_id` = 151 WHERE `company_id` IN (24, 151);
//UPDATE `company_adresses` SET `company_id` = 151 WHERE `company_id` IN (24, 151);
//UPDATE `arrivals` SET `company_id` = 151 WHERE `company_id` IN (24, 151);
//UPDATE `company_jobs` SET `company_id` = 151 WHERE `company_id` IN (24, 151);
//UPDATE `user_owners` SET `company_id` = 151 WHERE `company_id` IN (24, 151);
//DELETE FROM `companies` WHERE `id` = 24;
//
//
//UPDATE `company_files` SET `company_id` = 81 WHERE `company_id` IN (63, 81);
//UPDATE `company_categories` SET `company_id` = 81 WHERE `company_id` IN (63, 81);
//UPDATE `candidates` SET `company_id` = 81 WHERE `company_id` IN (63, 81);
//UPDATE `company_adresses` SET `company_id` = 81 WHERE `company_id` IN (63, 81);
//UPDATE `arrivals` SET `company_id` = 81 WHERE `company_id` IN (63, 81);
//UPDATE `company_jobs` SET `company_id` = 81 WHERE `company_id` IN (63, 81);
//UPDATE `user_owners` SET `company_id` = 81 WHERE `company_id` IN (63, 81);
//DELETE FROM `companies` WHERE `id` = 63;
//
//
