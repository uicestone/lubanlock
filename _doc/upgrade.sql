-- 原始数据修正
update people set email = null where email = '无';
update account set account = id where account is null;
update schedule set company = 1 where company is null;
delete from account where type in ('办案奖金','结案奖金','结案奖金储备');
ALTER TABLE `express` DROP FOREIGN KEY `express_ibfk_5`;
ALTER TABLE `express` DROP FOREIGN KEY `express_ibfk_3`;
update people set id = 32 where id = 1;
delete from company where id = 3;
update company set id = 3 where id = 1;
update express set company = 3 where company is null;

-- 将各大对象表的id顺序衔接，以便并表
ALTER TABLE  `account` DROP FOREIGN KEY  `account_ibfk_12` ;
update account set id = id + (SELECT MAX(id)+1 FROM people) order by id desc;
update account set account = account + (SELECT MAX(id)+1 FROM people) order by account desc;
ALTER TABLE  `account` ADD FOREIGN KEY (  `account` ) REFERENCES  `account` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;
update document set id = id + (SELECT MAX(id)+1 FROM account) order by id desc;
update project set id = id + (SELECT MAX(id)+1 FROM document) order by id desc;
update schedule set id = id + (SELECT MAX(id)+1 FROM project) order by id desc;
update express set id = id + (SELECT MAX(id)+1 FROM schedule) order by id desc;
update score set id = id + (SELECT MAX(id)+1 FROM express) order by id desc;

-- 导入company
insert into lubanlock.company (id,name,type,host,syscode,sysname)
select id,name,type,host,syscode,sysname from company;

SET FOREIGN_KEY_CHECKS=0;

-- 导入people
insert into lubanlock.object (id,type,num,name,company,user,time,time_insert)
select id, type, num, name, company, if(uid is null, 1 , uid), from_unixtime(time),from_unixtime(time_insert) from people;

-- 导入user
insert into lubanlock.user (id,name,email,alias,password,`roles`,last_ip,last_login,company)
select user.id,user.name,if(email = '', null, email),alias,password,`group`,lastip,from_unixtime(lastlogin), user.company
from user inner join people using (id);

-- 导入其他object
insert into lubanlock.object (id, type, name, company, user, time, time_insert)
select account,'资金',name,company,uid,from_unixtime(time),from_unixtime(time_insert)
from account group by account;

insert into lubanlock.object (id, type, name, company, user, time, time_insert)
select id,'文档',name,company,uid,from_unixtime(time),from_unixtime(time_insert)
from document;

insert into lubanlock.object (id, type, num, name, company, user, time, time_insert)
select id,'快递',num,content,company,uid,from_unixtime(time),from_unixtime(time)
from express;

insert into lubanlock.object (id, type, num, name, company, user, time, time_insert)
select id,type,num,name,company,uid,from_unixtime(time),from_unixtime(time_insert)
from project;

insert into lubanlock.object (id, type, name, company, user, time, time_insert)
select id,'日程',name,company,uid,from_unixtime(time),from_unixtime(time_insert)
from schedule;

insert into lubanlock.object (id, type, name, company, user, time, time_insert)
select score.id,'分数',indicator.name,2,uid,from_unixtime(time),from_unixtime(time)
from score inner join indicator on indicator.id = score.indicator;

-- 导入object_meta
insert ignore into lubanlock.object_meta (object,`key`,value,comment,user,time)
select people,name,content,comment,uid,from_unixtime(time) from people_profile;

insert ignore into lubanlock.object_meta (object,`key`,value,comment,user,time)
select project,name,content,comment,uid,from_unixtime(time) from project_profile;

insert ignore into lubanlock.object_meta (object,`key`,value,comment,user,time)
select schedule,name,content,comment,uid,from_unixtime(time) from schedule_profile;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select account,'科目',`subject`,uid,from_unixtime(time)
from `account` where `subject` is not null and `subject` != '' group by account;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select account,'数额',sum(`amount`),uid,from_unixtime(time)
from `account` group by account;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select account,'计入创收',if(`count`,1,''),uid,from_unixtime(time)
from `account` where id = account;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select account,'预估到账日期',max(date),uid,from_unixtime(time)
from `account` group by account;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select account,'备注',`comment`,uid,from_unixtime(time)
from `account` where `comment` is not null and `comment` != '' group by account;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select account.account,'发票未开','1',1,0
from `account_label` inner join account where label_name = '发票未开';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'文件名',`filename`,uid,from_unixtime(time)
from `document`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'扩展名',`extname`,uid,from_unixtime(time)
from `document`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'大小',`size`,uid,from_unixtime(time)
from `document`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'备注',`comment`,uid,from_unixtime(time)
from `document`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'收件地址',`destination`,uid,from_unixtime(time)
from `express`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'费用',`fee`,uid,from_unixtime(time)
from `express` where fee != 0;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'数量',`amount`,uid,from_unixtime(time)
from `express`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'英文名',`name_en`,uid,from_unixtime(time)
from `people` where `name_en` is not null and `name_en` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'拼音',`name_pinyin`,uid,from_unixtime(time)
from `people` where `name_pinyin` is not null and `name_pinyin` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'简称',`abbreviation`,uid,from_unixtime(time)
from `people` where `abbreviation` is not null and `abbreviation` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'性别',`gender`,uid,from_unixtime(time)
from `people` where `gender` is not null and `gender` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'电话',`phone`,uid,from_unixtime(time)
from `people` where `phone` is not null and `phone` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'电子邮件',`email`,uid,from_unixtime(time)
from `people` where `email` is not null and `email` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'身份证',`id_card`,uid,from_unixtime(time)
from `people` where `id_card` is not null and `id_card` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'工作单位',`work_for`,uid,from_unixtime(time)
from `people` where `work_for` is not null and `work_for` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'职位',`position`,uid,from_unixtime(time)
from `people` where `position` is not null and `position` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'所在地',`city`,uid,from_unixtime(time)
from `people` where `city` is not null and `city` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'民族',`race`,uid,from_unixtime(time)
from `people` where `race` is not null and `race` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'在办',if(`active`,'1',''),uid,from_unixtime(time)
from `project`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'预估结案日期',`end`,uid,from_unixtime(time)
from `project` where active = 1 and type = 'cases';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'报价',`quote`,uid,from_unixtime(time)
from `project` where `quote` is not null and `quote` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'焦点',`focus`,uid,from_unixtime(time)
from `project` where `focus` is not null and `focus` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'案情简介',`summary`,uid,from_unixtime(time)
from `project` where `summary` is not null and `summary` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'备注',`comment`,uid,from_unixtime(time)
from `project` where `comment` is not null and `comment` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'内容',`content`,uid,from_unixtime(time)
from `schedule` where `content` is not null and `content` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'截止日期',`deadline`,uid,from_unixtime(time)
from `schedule` where `deadline` is not null and `deadline` != 0;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'自报小时',`hours_own`,uid,from_unixtime(time)
from `schedule`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'审核小时',`hours_checked`,uid,from_unixtime(time)
from `schedule`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'全天',if(`all_day`,'1',''),uid,from_unixtime(time)
from `schedule`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'在任务列表中',if(`in_todo_list`,'1',''),uid,from_unixtime(time)
from `schedule`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'备注',`comment`,uid,from_unixtime(time)
from `schedule` where `comment` is not null and `comment` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'分数',`score`,uid,from_unixtime(time)
from `score`;

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'备注',`comment`,uid,from_unixtime(time)
from `score` where `comment` is not null and `comment` != '';

insert ignore into lubanlock.object_meta (object,`key`,value,user,time)
select id,'职称',title,1,0 from staff where title is not null and title != '';

-- 导入object_status
insert into lubanlock.object_status (object,name,date,comment,user,time)
select account,'到账',date,comment,uid,from_unixtime(time)
from account where received = 1;

insert into lubanlock.object_status (object,name,date,user,time)
select id,'首次接洽',first_contact,uid,from_unixtime(time)
from project where first_contact is not null;

insert into lubanlock.object_status (object,name,date,user,time)
select id,'立案',time_contract,uid,from_unixtime(time)
from project where time_contract is not null;

insert into lubanlock.object_status (object,name,date,user,time)
select id,'结案',end,uid,from_unixtime(time)
from project where active = 0 and end is not null;

insert into lubanlock.object_status (object,name,date,user,time)
select id,'开始',from_unixtime(start),uid,from_unixtime(time)
from schedule where start != 0;

insert into lubanlock.object_status (object,name,date,user,time)
select id,'结束',from_unixtime(end),uid,from_unixtime(time)
from schedule where end != 0;

insert into lubanlock.object_status (object,name,date,user,time)
select id,'完成',from_unixtime(time),uid,from_unixtime(time)
from schedule where completed = 1;

-- 导入object_tag
insert ignore into lubanlock.tag (name)
select name from label;

insert ignore into lubanlock.tag (name)
select type from account group by type;

insert ignore into lubanlock.tag_taxonomy (tag,taxonomy)
select tag.id,'类型' from account inner join lubanlock.tag on tag.name = account.type group by account.type;

insert ignore into lubanlock.object_tag (object,tag_taxonomy,user,time)
select account.account,tag_taxonomy.id,1,0
from account inner join lubanlock.tag on tag.name = account.type inner join lubanlock.tag_taxonomy on tag_taxonomy.tag = tag.id and tag_taxonomy.taxonomy = '类型';

--
insert ignore into lubanlock.tag_taxonomy (tag,taxonomy)
select tag.id,'类型' from document_label inner join lubanlock.tag on tag.name = document_label.label_name group by document_label.label_name;

insert ignore into lubanlock.object_tag (object,tag_taxonomy,user,time)
select document_label.document,tag_taxonomy.id,1,0
from document_label inner join lubanlock.tag on tag.name = document_label.label_name inner join lubanlock.tag_taxonomy on tag_taxonomy.tag = tag.id and tag_taxonomy.taxonomy = '类型';
--
insert ignore into lubanlock.tag_taxonomy (tag,taxonomy)
select tag.id,type from people_label inner join lubanlock.tag on tag.name = people_label.label_name group by people_label.label_name;

insert ignore into lubanlock.object_tag (object,tag_taxonomy,user,time)
select people_label.people,tag_taxonomy.id,1,0
from people_label inner join lubanlock.tag on tag.name = people_label.label_name inner join lubanlock.tag_taxonomy on tag_taxonomy.tag = tag.id and tag_taxonomy.taxonomy = people_label.type;
--
insert ignore into lubanlock.tag_taxonomy (tag,taxonomy)
select tag.id,type from project_label inner join lubanlock.tag on tag.name = project_label.label_name group by project_label.label_name;

insert ignore into lubanlock.object_tag (object,tag_taxonomy,user,time)
select project_label.project,tag_taxonomy.id,1,0
from project_label inner join lubanlock.tag on tag.name = project_label.label_name inner join lubanlock.tag_taxonomy on tag_taxonomy.tag = tag.id and tag_taxonomy.taxonomy = project_label.type;
--
insert ignore into lubanlock.tag_taxonomy (tag,taxonomy)
select tag.id,'' from schedule_label inner join lubanlock.tag on tag.name = schedule_label.label_name group by schedule_label.label_name;

insert ignore into lubanlock.object_tag (object,tag_taxonomy,user,time)
select schedule_label.schedule,tag_taxonomy.id,1,0
from schedule_label inner join lubanlock.tag on tag.name = schedule_label.label_name inner join lubanlock.tag_taxonomy on tag_taxonomy.tag = tag.id and tag_taxonomy.taxonomy = '';

drop table if exists taxonomy_count ;
create temporary table taxonomy_count select tag_taxonomy,count(*) count from lubanlock.object_tag group by tag_taxonomy;
update lubanlock.tag_taxonomy inner join taxonomy_count on taxonomy_count.tag_taxonomy = tag_taxonomy.id
set tag_taxonomy.count = taxonomy_count.count;
-- -------------------------------------------------------------------------------------------------
-- 导入object_relationship
-- account
insert into lubanlock.object_relationship (object,relative,relation,user,time)
select account,project,'案件',uid,from_unixtime(time_insert) from account group by account;

insert into lubanlock.object_relationship (object,relative,relation,user,time)
select account,people,'付款人',uid,from_unixtime(time_insert) from account group by account;

-- express
insert into lubanlock.object_relationship (object,relative,relation,user,time)
select id,sender,'寄送人',uid,from_unixtime(time) from express;

-- people
insert into lubanlock.object_relationship (object,relative,relation,user,time)
select id,staff,'介绍职员',uid,from_unixtime(time) from people where staff	 is not null;

-- people_relationship
insert ignore into lubanlock.object_relationship (object,relative,relation,user,time)
select people,relative,if(relation is null,'',relation),uid,from_unixtime(time) from people_relationship;

-- project_document
insert into lubanlock.object_relationship (object,relative,relation,user,time)
select project,document,'文档',uid,from_unixtime(time) from project_document;

-- project_relationship
insert into lubanlock.object_relationship (object,relative,relation,user,time)
select project,relative,if(relation is null,'',relation),1,0 from project_relationship;

-- project_people
insert into lubanlock.object_relationship (object,relative,relation,user,time)
select project,people,role,uid,from_unixtime(time) from project_people;

-- schedule_people
insert into lubanlock.object_relationship (object,relative,relation,user,time)
select schedule,people,'people',1,0 from schedule_people;

-- user_config
insert into lubanlock.user_config (user,`key`,value)
SELECT uid,'taskboard_sort_data',sort_data FROM `schedule_taskboard`;

-- dialog & message
insert into lubanlock.object (type, name, company, user, time, time_insert, flag)
select 'message',LEFT(content,255),user.company,uid,from_unixtime(time),from_unixtime(time),message.id from message inner join user on message.uid = user.id;

insert into lubanlock.object (type, company, user, time, time_insert, flag)
select 'dialog',user.company,uid,from_unixtime(time),from_unixtime(time),dialog.id from dialog inner join user on dialog.uid = user.id;

insert into lubanlock.object_relationship (object, relative, relation,user,time)
select object.id, dialog_user.user, 'user',1,0 from dialog_user inner join lubanlock.object on object.flag = dialog_user.dialog and object.type = 'dialog';

insert into lubanlock.object_relationship (object, relative,relation,user,time)
select dialog_object.id, message_object.id,'message',1,0
from dialog_message 
inner join lubanlock.object dialog_object on dialog_object.flag = dialog_message.dialog and dialog_object.type = 'dialog' 
inner join lubanlock.object message_object on message_object.flag = dialog_message.message and message_object.type = 'message';

insert into lubanlock.object_relationship (object,relative,relation,user,time)
select message_object.id, message_user.user,'user',1,0
from message_user 
inner join lubanlock.object message_object on message_object.flag = message_user.message and message_object.type = 'message';

insert into lubanlock.object_relationship (object,relative,relation,user,time)
select message_object.id, message_document.document,'document',1,0
from message_document inner join lubanlock.object message_object on message_object.flag = message_document.message and message_object.type = 'message';

SET FOREIGN_KEY_CHECKS=1;

-- 将所有user为1和time为0 的更新为相关值