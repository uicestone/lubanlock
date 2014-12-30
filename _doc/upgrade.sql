-- 原始数据修正
update people set email = null where email = '无';
update people set uid = 8000 where uid = 6343 and company = 2;
update account set account = id where account is null;
delete from account where type in ('办案奖金', '结案奖金', '结案奖金储备');
update account inner join project on account.project = project.id set account.name = concat(subject, project.name) where account.name is null;
update account set name = type where name is null;update schedule set company = 1 where company is null;
ALTER TABLE `express` DROP FOREIGN KEY `express_ibfk_5`;
ALTER TABLE `express` DROP FOREIGN KEY `express_ibfk_3`;
update express set company = 1 where company is null;
delete from document where extname = '.blob';
update document set name = filename	where name = '';
update people set type = '客户' where type = 'client';
update project set type = '案件' where type = 'cases';
update people set type = '联系人' where type = 'contact';
update project set type = '项目' where type = 'project';
update project set type = '咨询' where type = 'query';
update people set type = '职员' where type = 'staff';
update people set type = '班级' where type = 'classes';
update people set type = '教研组' where type = 'course_group';
update people set type = '年级' where type = 'grade';
update people set type = '家长' where type = 'parent';
update people set type = '社团' where type = 'society';
update people set type = '学生' where type = 'student';
update people set type = '人员' where type = 'people';
update people set type = '备课组' where type = 'teacher_group';
update people set type = '团队' where type = 'team';
update project set type = '评价' where type = 'evaluation';
update project set type = '考试' where type = 'exam';
update project set type = '试卷' where type = 'exam_paper';

-- 导入company
SET @company = 2;
insert into lubanlock.company (id, name, type, host, syscode, sysname)
select id, name, type, host, syscode, sysname from company where id = @company;

ALTER TABLE lubanlock.`object` ADD `prev_id` INT DEFAULT NULL, ADD INDEX (`prev_id`) ;
ALTER TABLE lubanlock.`object` ADD `prev_table` VARCHAR(32) DEFAULT NULL, ADD INDEX (`prev_table`) ;

SET FOREIGN_KEY_CHECKS=0;

-- 导入people
insert into lubanlock.object (prev_table, prev_id, type, num, name, company, user, time, time_insert)
select 'people', id, type, num, name, company, uid, from_unixtime(time), from_unixtime(time_insert)
from people where people.company = @company and name != '' and name is not null;

-- 导入user
insert into lubanlock.user (id, name, email, password, `roles`, last_ip, last_login, company)
select object.id, user.name, if(email = '', null, email), password, `group`, lastip, from_unixtime(lastlogin), user.company
from user inner join people using (id) inner join lubanlock.object on object.prev_id = user.id and object.prev_table = 'people'
where user.company = @company;

-- 导入其他object
insert into lubanlock.object (prev_table, prev_id, type, name, company, user, time, time_insert)
select 'account', account, '资金', name, company, uid, from_unixtime(time), from_unixtime(time_insert)
from account where account.company = @company group by account;

insert into lubanlock.object (prev_table, prev_id, type, name, company, user, time, time_insert)
select 'document', id, '文件', name, company, uid, from_unixtime(time), from_unixtime(time_insert)
from document where document.company = @company and name != '' and name is not null;

insert into lubanlock.object (prev_table, prev_id, type, num, name, company, user, time, time_insert)
select 'express', id, '快递', num, content, company, uid, from_unixtime(time), from_unixtime(time)
from express where express.company = @company;

insert into lubanlock.object (prev_table, prev_id, type, num, name, company, user, time, time_insert)
select 'project', id, type, num, name, company, uid, from_unixtime(time), from_unixtime(time_insert)
from project where project.company = @company and name != '' and name is not null;

insert into lubanlock.object (prev_table, prev_id, type, name, company, user, time, time_insert)
select 'schedule', id, '日程', name, company, uid, from_unixtime(time), from_unixtime(time_insert)
from schedule where schedule.company = @company and name != '' and name is not null;

-- 导入object_meta
insert ignore into lubanlock.object_meta (object, `key`, value, comment, user, time)
select object.id, people_profile.name, content, comment, if(uid is null, object.user, uid), if(people_profile.time, from_unixtime(people_profile.time), object.time)
from people_profile
inner join lubanlock.object on object.prev_id = people_profile.people and object.prev_table = 'people'
where content != '';

insert ignore into lubanlock.object_meta (object, `key`, value, comment, user, time)
select object.id, project_profile.name, content, comment, if(uid is null, object.user, uid), if(project_profile.time, from_unixtime(project_profile.time), object.time)
from project_profile
inner join lubanlock.object on object.prev_id = project_profile.project and object.prev_table = 'project';

insert ignore into lubanlock.object_meta (object, `key`, value, comment, user, time)
select object.id, schedule_profile.name, content, comment, uid, from_unixtime(schedule_profile.time) from schedule_profile
inner join lubanlock.object on object.prev_id = schedule_profile.schedule and object.prev_table = 'schedule';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '科目', `subject`, uid, object.time
from `account`
inner join lubanlock.object on object.prev_id = account and object.prev_table = 'account'
where `subject` is not null and `subject` != '' group by account;

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '数额', sum(`amount`), uid, object.time
from `account`
inner join lubanlock.object on object.prev_id = account and object.prev_table = 'account'
group by account;

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '计入创收', if(`count`, 1, ''), uid, object.time
from `account`
inner join lubanlock.object on object.prev_id = account and object.prev_table = 'account'
where account.id = account;

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '预估到账日期', max(date), uid, object.time
from `account`
inner join lubanlock.object on object.prev_id = account and object.prev_table = 'account'
group by account;

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '备注', `comment`, uid, object.time
from `account`
inner join lubanlock.object on object.prev_id = account and object.prev_table = 'account'
where `comment` is not null and `comment` != '' group by account;

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '发票未开', '1', object.user, object.time
from `account_label` inner join account
inner join lubanlock.object on object.prev_id = account.account and object.prev_table = 'account'
where label_name = '发票未开';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, 'filename', `filename`, uid, object.time
from `document`
inner join lubanlock.object on object.prev_id = document.id and object.prev_table = 'document';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, 'extension', `extname`, uid, object.time
from `document`
inner join lubanlock.object on object.prev_id = document.id and object.prev_table = 'document';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, 'size', `size`, uid, object.time
from `document`
inner join lubanlock.object on object.prev_id = document.id and object.prev_table = 'document';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '备注', `comment`, uid, object.time
from `document`
inner join lubanlock.object on object.prev_id = document.id and object.prev_table = 'document';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '收件地址', `destination`, uid, object.time
from `express`
inner join lubanlock.object on object.prev_id = express.id and object.prev_table = 'express';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '费用', `fee`, uid, object.time
from `express`
inner join lubanlock.object on object.prev_id = express.id and object.prev_table = 'express'
where fee != 0;

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '数量', `amount`, uid, object.time
from `express`
inner join lubanlock.object on object.prev_id = express.id and object.prev_table = 'express';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '英文名', `name_en`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `name_en` is not null and `name_en` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '拼音', `name_pinyin`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `name_pinyin` is not null and `name_pinyin` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '简称', `abbreviation`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `abbreviation` is not null and `abbreviation` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '性别', `gender`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `gender` is not null and `gender` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '电话', `phone`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `phone` is not null and `phone` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '电子邮件', `email`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `email` is not null and `email` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '身份证', `id_card`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `id_card` is not null and `id_card` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '工作单位', `work_for`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `work_for` is not null and `work_for` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '职位', `position`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `position` is not null and `position` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '所在地', `city`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `city` is not null and `city` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '民族', `race`, uid, object.time
from `people`
inner join lubanlock.object on object.prev_id = people.id and object.prev_table = 'people'
where `race` is not null and `race` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '在办', if(`active`, '1', ''), uid, object.time
from `project`
inner join lubanlock.object on object.prev_id = project.id and object.prev_table = 'project';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '预估结案日期', `end`, uid, object.time
from `project`
inner join lubanlock.object on object.prev_id = project.id and object.prev_table = 'project'
where active = 1 and project.type = 'cases';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '报价', `quote`, uid, object.time
from `project`
inner join lubanlock.object on object.prev_id = project.id and object.prev_table = 'project'
where `quote` is not null and `quote` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '焦点', `focus`, uid, object.time
from `project`
inner join lubanlock.object on object.prev_id = project.id and object.prev_table = 'project'
where `focus` is not null and `focus` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '案情简介', `summary`, uid, object.time
from `project`
inner join lubanlock.object on object.prev_id = project.id and object.prev_table = 'project'
where `summary` is not null and `summary` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '备注', `comment`, uid, object.time
from `project`
inner join lubanlock.object on object.prev_id = project.id and object.prev_table = 'project'
where `comment` is not null and `comment` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '内容', `content`, uid, object.time
from `schedule`
inner join lubanlock.object on object.prev_id = schedule.id and object.prev_table = 'schedule'
where `content` is not null and `content` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, 'due_date', `deadline`, uid, object.time
from `schedule`
inner join lubanlock.object on object.prev_id = schedule.id and object.prev_table = 'schedule'
where `deadline` is not null and `deadline` != 0;

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, 'hours', `hours_own`, uid, object.time
from `schedule`
inner join lubanlock.object on object.prev_id = schedule.id and object.prev_table = 'schedule';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, 'hours_reviewed', `hours_checked`, uid, object.time
from `schedule`
inner join lubanlock.object on object.prev_id = schedule.id and object.prev_table = 'schedule';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, 'is_allday', if(`all_day`, '1', ''), uid, object.time
from `schedule`
inner join lubanlock.object on object.prev_id = schedule.id and object.prev_table = 'schedule';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, 'is_in_task_list', if(`in_todo_list`, '1', ''), uid, object.time
from `schedule`
inner join lubanlock.object on object.prev_id = schedule.id and object.prev_table = 'schedule';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '备注', `comment`, uid, object.time
from `schedule`
inner join lubanlock.object on object.prev_id = schedule.id and object.prev_table = 'schedule'
where `comment` is not null and `comment` != '';

insert ignore into lubanlock.object_meta (object, `key`, value, user, time)
select object.id, '职称', title, object.user, object.time from staff
inner join lubanlock.object on object.prev_id = staff.id and object.prev_table = 'people'
where title is not null and title != '';

-- 导入object_status
insert into lubanlock.object_status (object, name, date, comment, user, time)
select object.id, '到账', date, comment, uid, object.time
from account
inner join lubanlock.object on object.prev_id = account.account and object.prev_table = 'account'
where received = 1;

insert into lubanlock.object_status (object, name, date, user, time)
select object.id, '首次接洽', first_contact, uid, object.time
from project
inner join lubanlock.object on object.prev_id = project.id and object.prev_table = 'account'
where first_contact is not null;

insert into lubanlock.object_status (object, name, date, user, time)
select object.id, '立案', time_contract, uid, object.time
from project
inner join lubanlock.object on object.prev_id = project.id and object.prev_table = 'project'
where time_contract is not null;

insert into lubanlock.object_status (object, name, date, user, time)
select object.id, '结案', end, uid, object.time
from project
inner join lubanlock.object on object.prev_id = project.id and object.prev_table = 'project'
where active = 0 and end is not null;

insert into lubanlock.object_status (object, name, date, user, time)
select object.id, '开始', from_unixtime(start), uid, object.time
from schedule
inner join lubanlock.object on object.prev_id = schedule.id and object.prev_table = 'schedule'
where start != 0;

insert into lubanlock.object_status (object, name, date, user, time)
select object.id, '结束', from_unixtime(end), uid, object.time
from schedule
inner join lubanlock.object on object.prev_id = schedule.id and object.prev_table = 'schedule'
where end != 0;

insert into lubanlock.object_status (object, name, date, user, time)
select object.id, '完成', object.time, uid, object.time
from schedule
inner join lubanlock.object on object.prev_id = schedule.id and object.prev_table = 'schedule'
where completed = 1;

-- 导入object_tag
insert ignore into lubanlock.tag (name)
select name from label;

insert ignore into lubanlock.tag (name)
select type from account group by type;

insert ignore into lubanlock.tag_taxonomy (tag, taxonomy)
select tag.id, '类型' from account inner join lubanlock.tag on tag.name = account.type group by account.type;

insert ignore into lubanlock.object_tag (object, tag_taxonomy, user, time)
select object.id, tag_taxonomy.id, 1, 0
from account inner join lubanlock.tag on tag.name = account.type inner join lubanlock.tag_taxonomy on tag_taxonomy.tag = tag.id and tag_taxonomy.taxonomy = '类型'
inner join lubanlock.object on object.prev_id = account.account and object.prev_table = 'account';
--
insert ignore into lubanlock.tag_taxonomy (tag, taxonomy)
select tag.id, '类型' from document_label inner join lubanlock.tag on tag.name = document_label.label_name group by document_label.label_name;

insert ignore into lubanlock.object_tag (object, tag_taxonomy, user, time)
select object.id, tag_taxonomy.id, object.user, object.time
from document_label inner join lubanlock.tag on tag.name = document_label.label_name inner join lubanlock.tag_taxonomy on tag_taxonomy.tag = tag.id and tag_taxonomy.taxonomy = '类型'
inner join lubanlock.object on object.prev_id = document and object.prev_table = 'document';
--
insert ignore into lubanlock.tag_taxonomy (tag, taxonomy)
select tag.id, type from people_label inner join lubanlock.tag on tag.name = people_label.label_name group by people_label.label_name;

insert ignore into lubanlock.object_tag (object, tag_taxonomy, user, time)
select object.id, tag_taxonomy.id, object.user, from_unixtime(object.time)
from people_label inner join lubanlock.tag on tag.name = people_label.label_name inner join lubanlock.tag_taxonomy on tag_taxonomy.tag = tag.id and tag_taxonomy.taxonomy = people_label.type
inner join lubanlock.object on object.prev_id = people and object.prev_table = 'people';
--
insert ignore into lubanlock.tag_taxonomy (tag, taxonomy)
select tag.id, type from project_label inner join lubanlock.tag on tag.name = project_label.label_name group by project_label.label_name;

insert ignore into lubanlock.object_tag (object, tag_taxonomy, user, time)
select object.id, tag_taxonomy.id, object.user, from_unixtime(object.time)
from project_label inner join lubanlock.tag on tag.name = project_label.label_name inner join lubanlock.tag_taxonomy on tag_taxonomy.tag = tag.id and tag_taxonomy.taxonomy = project_label.type
inner join lubanlock.object on object.prev_id = project and object.prev_table = 'project';
--
insert ignore into lubanlock.tag_taxonomy (tag, taxonomy)
select tag.id, '' from schedule_label inner join lubanlock.tag on tag.name = schedule_label.label_name group by schedule_label.label_name;

insert ignore into lubanlock.object_tag (object, tag_taxonomy, user, time)
select object.id, tag_taxonomy.id, object.user, from_unixtime(object.time)
from schedule_label inner join lubanlock.tag on tag.name = schedule_label.label_name inner join lubanlock.tag_taxonomy on tag_taxonomy.tag = tag.id and tag_taxonomy.taxonomy = ''
inner join lubanlock.object on object.prev_id = schedule and object.prev_table = 'schedule';

drop table if exists taxonomy_count ;
create temporary table taxonomy_count select tag_taxonomy, count(*) count from lubanlock.object_tag group by tag_taxonomy;
update lubanlock.tag_taxonomy inner join taxonomy_count on taxonomy_count.tag_taxonomy = tag_taxonomy.id
set tag_taxonomy.count = taxonomy_count.count;
update tax_taxonomy set taxonomy = '类型' where taxonomy = '';
update tag_taxonomy inner join tag on tag.id = tag_taxonomy.tag
set tag_taxonomy.name = concat(tag_taxonomy.taxonomy, ' - ', tag.name);
delete from tag_taxonomy where count = 0;
delete from tag where id not in (select tag from tag_taxonomy);
delete from object_tag where tag_taxonomy in (select id from tag_taxonomy where tag in (select id from tag where name = ''));
delete from tag_taxonomy where tag in (select id from tag where name = '');
delete from tag where name = '';
-- -------------------------------------------------------------------------------------------------
-- 导入object_relationship
-- account
insert into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select account_object.id, project_object.id, '案件', 1, uid, account_object.time_insert from account
inner join lubanlock.object account_object on account_object.prev_id = account.account and account_object.prev_table = 'account'
inner join lubanlock.object project_object on project_object.prev_id = account.project and account_object.prev_table = 'project'
group by account;

insert into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select account, people, '付款人', 1, uid, account_object.time_insert from account
inner join lubanlock.object account_object on account_object.prev_id = account.account and account_object.prev_table = 'account'
inner join lubanlock.object people_object on people_object.prev_id = account.people and people_object.prev_table = 'people'
group by account;

-- express
insert into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select express_object.id, sender_object.id, '寄送人', 1, uid, express_object.time from express
inner join lubanlock.object express_object on express_object.prev_id = express.id and express_object.prev_table = 'express'
inner join lubanlock.object sender_object on sender_object.prev_id = express.sender and sender_object.prev_table = 'people';

-- people
insert into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select people_object.id, staff_object.id, '介绍职员', 1, uid, people_object.time from people
inner join lubanlock.object people_object on people_object.prev_id = people.id and people_object.prev_table = 'people'
inner join lubanlock.object staff_object on staff_object.prev_id = people.staff and staff_object.prev_table = 'people'
where staff is not null;

-- people_relationship
insert ignore into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select people_object.id, relative_object.id, if(relation is null or relation = '', 'member', relation), is_on, uid, people_object.time from people_relationship
inner join lubanlock.object people_object on people_object.prev_id = people and people_object.prev_table = 'people'
inner join lubanlock.object relative_object on relative_object.prev_id = relative and relative_object.prev_table = 'people';

-- project_document
insert into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select project_object.id, document_object.id, '文件', 1, uid, project_object.time from project_document
inner join lubanlock.object project_object on project_object.prev_id = project and project_object.prev_table = 'project'
inner join lubanlock.object document_object on document_object.prev_id = document and document_object.prev_table = 'document';

-- project_relationship
insert into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select project_object.id, relative_object.id, if(relation is null, '学科', relation), 1, project_object.user, project_object.time from project_relationship
inner join lubanlock.object project_object on project_object.prev_id = project and project_object.prev_table = 'project'
inner join lubanlock.object relative_object on relative_object.prev_id = relative and relative_object.prev_table = 'project';

-- project_people
insert into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select project_object.id, people_object.id, if(role = '' or role is null, '参与人', role), 1, uid, project_object.time from project_people
inner join lubanlock.object project_object on project_object.prev_id = project and project_object.prev_table = 'project'
inner join lubanlock.object people_object on people_object.prev_id = people and people_object.prev_table = 'people';

-- schedule_people
insert into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select schedule_object.id, people_object.id, '参与人', if(deleted, null, 1), schedule_object.user, from_unixtime(schedule_object.time) from schedule_people
inner join lubanlock.object schedule_object on schedule_object.prev_id = schedule and schedule_object.prev_table = 'schedule'
inner join lubanlock.object people_object on people_object.prev_id = people and people_object.prev_table = 'people';
-- schedule_people metas
insert into lubanlock.object_relationship_meta (relationship, `key`, value, user, time)
select object_relationship.id, 'involved', schedule_people.enrolled,  schedule_object.user, schedule_object.time
from schedule_people
inner join lubanlock.object schedule_object on schedule_object.prev_id = schedule_people.schedule and schedule_object.prev_table = 'schedule'
inner join lubanlock.object people_object on people_object.prev_id = schedule_people.people and people_object.prev_table = 'people'
inner join lubanlock.object_relationship on schedule_object.id = object_relationship.object and people_object.id = object_relationship.relative
where schedule_people.enrolled = 1;

insert into lubanlock.object_relationship_meta (relationship, `key`, value, user, time)
select object_relationship.id, 'in_task_list', schedule_people.in_todo_list,  schedule_object.user, schedule_object.time
from schedule_people
inner join lubanlock.object schedule_object on schedule_object.prev_id = schedule_people.schedule and schedule_object.prev_table = 'schedule'
inner join lubanlock.object people_object on people_object.prev_id = schedule_people.people and people_object.prev_table = 'people'
inner join lubanlock.object_relationship on schedule_object.id = object_relationship.object and people_object.id = object_relationship.relative
where schedule_people.in_todo_list = 1;
-- document permission
insert into lubanlock.object_permission (`object`, `user`, `read`, `write`, `grant`, time)
select object.id, people, CAST((`mod` & 1) / 1 AS SIGNED), CAST((`mod` & 2) / 2 AS SIGNED), CAST((`mod` & 4) / 4 AS SIGNED), object.time
from document_mod
inner join lubanlock.object on object.prev_id = document and object.prev_table = 'document';

-- user_config

-- dialog & message
-- insert dialog
insert into lubanlock.object (type, name, company, user, time, time_insert, prev_table, prev_id)
select '消息', content, user.company, uid, from_unixtime(time), from_unixtime(time), 'message', message.id from message inner join user on message.uid = user.id
where user.company = @company;
-- insert message
insert into lubanlock.object (type, name, num, company, user, time, time_insert, prev_table, prev_id)
select '对话', dialog_user.title, dialog_user.dialog, user.company, dialog_user.user, from_unixtime(last_message.time), from_unixtime(dialog.time), 'dialog', dialog_user.dialog
from dialog_user
inner join user on dialog_user.user = user.id
inner join dialog on dialog.id = dialog_user.dialog
inner join message last_message on dialog.last_message = last_message.id
where dialog.company = @company;
-- insert message content
insert into lubanlock.object_meta (object, `key`, value)
select message_object.id, 'content', message.content
from message
inner join lubanlock.object message_object on message_object.prev_id = message.id and message_object.prev_table = 'message';
-- insert message into dialog
insert ignore/*there's duplicate in syssh.dialog_message, message_user and dialog_message*/ into lubanlock.object_relationship (object, relative, relation, is_on, visibility, user, time)
select dialog_object.id, message_object.id, 'message', if(message_user.deleted, null, 1), if(message_user.read, 0, 1), message.uid, from_unixtime(message.time)
from dialog_message
inner join message on message.id = dialog_message.message
inner join lubanlock.object dialog_object on dialog_object.num = dialog_message.dialog and dialog_object.prev_table = 'dialog'
inner join lubanlock.object message_object on message_object.prev_id = dialog_message.message and message_object.prev_table = 'message'
inner join message_user on message_user.message = dialog_message.message and message_user.user = dialog_object.user;
-- insert last message into dialog
insert into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select dialog_object.id, last_message_object.id, 'last_message', 1, last_message_object.user, last_message_object.time
from dialog
inner join lubanlock.object dialog_object on dialog_object.prev_id = dialog.id and dialog_object.prev_table = 'dialog'
inner join lubanlock.object last_message_object on last_message_object.prev_id = dialog.last_message and last_message_object.prev_table = 'message';
-- insert document into message
insert into lubanlock.object_relationship (object, relative, relation, is_on, user, time)
select message_object.id, document_object.id, 'attachment', 1, message_object.user, message_object.time
from message_document
inner join lubanlock.object message_object on message_object.prev_id = message_document.message and message_object.prev_table = 'message'
inner join lubanlock.object document_object on document_object.prev_id = message_document.document and document_object.prev_table = 'document';
-- caculate dialog unread messages
insert into lubanlock.object_meta (object, `key`, value, user, time)
select dialog_object.id, 'unread_messages', count(*), dialog_object.user, dialog_object.time
from lubanlock.object dialog_object
inner join lubanlock.object_relationship dialog_message on dialog_message.object = dialog_object.id
where dialog_object.type = '对话' and dialog_message.is_on = 1 and dialog_message.visibility = 1
group by dialog_message.object;
-- fix new user id
update lubanlock.object inner join lubanlock.object people on people.prev_id = object.user and people.prev_table = 'people' set object.user = people.id, object.time = object.time;
update lubanlock.object_meta inner join lubanlock.object people on people.prev_id = object_meta.user and people.prev_table = 'people' set object_meta.user = people.id, object_meta.time = object_meta.time;
update lubanlock.object_permission inner join lubanlock.object people on people.prev_id = object_permission.user and people.prev_table = 'people' set object_permission.user = people.id, object_permission.time = object_permission.time;
update lubanlock.object_relationship inner join lubanlock.object people on people.prev_id = object_relationship.user and people.prev_table = 'people' set object_relationship.user = people.id, object_relationship.time = object_relationship.time;
update lubanlock.object_relationship_meta inner join lubanlock.object people on people.prev_id = object_relationship_meta.user and people.prev_table = 'people' set object_relationship_meta.user = people.id, object_relationship_meta.time = object_relationship_meta.time;
update lubanlock.object_status inner join lubanlock.object people on people.prev_id = object_status.user and people.prev_table = 'people' set object_status.user = people.id, object_status.time = object_status.time;
update lubanlock.object_tag inner join lubanlock.object people on people.prev_id = object_tag.user and people.prev_table = 'people' set object_tag.user = people.id, object_tag.time = object_tag.time;

SET FOREIGN_KEY_CHECKS=1;

update lubanlock.company set id = 1 where id = @company;
