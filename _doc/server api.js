/**
 * data structure definition
 * /

/**
 *  对象数据结构
 */
var object = {
	"id":0,
	"type":"",//类型，如"人员", "联系人", "案件", "事务"
	"num":"",//编号，字符串
	"name":"",//显示名称
	"meta":meta,
	"relative":relative,
	"status":status,
	"tag":tag
};

/*
 * 元数据
 * 存放对象的普通属性，如人员的电话，案件的简介，考试的参与人数、班级的教室等
 */
var meta = {
	"{key}": ["{value1}", "{value2}"]
};

/**
 * 关联对象
 * 描述与本对象关联的对象的简要信息，以及关系
 */
var relative = {
	"{relation}": [
		{
			"id": 0,
			"type":"",//关联对象的object.type
			"name": "",//关联对象的object.name
			"num":"",//关联对象的object.num
			"relationship_num":"",//关系的编号
			meta:{//关系的元数据
				"{key}":"{value}"
			}
		}
	]
};

/*
 * 一个对象的各种与时间有关的状态
 * 如，案件的立案时间，帐目的应收帐款时间，日程的底线时间
 */
var status = [
	{
		"name":"",
		"date":"1970-01-01 00:00:00"
	}
];

/*
 * 一个对象的标签，用于搜索和分类
 */
var tag = {
	"{taxonomy}": ["{tag1}","{tag2}"]
};

/**
 * server api
 */
var api = [
	/*整个对象的CURD*/
	{
		"name":"获取单个对象",
		"request":{
			"method":"GET",
			"path":"/object/" + object.id,
			"query":{
				"with_meta":true | getMetaArgs,
				"with_relative":true | getStatusArgs,
				"with_status":true,
				"with_tag":true,
				"with":["meta","relative","status","tag"]
			}
		},
		"response":{
			"contentType":"application/json",/*contentType默认都为application/json*/
			"body":object
		}
	},
	{
		"name":"创建单个对象",
		"request":{
			"method":"PUT/POST",
			"path":"/object",
			"body":object//without id, uid, time, timeinsert attributes
		},
		response:{
			"body":object
		}
	},
	{
		"name":"更新单个对象",
		"request":{
			"method":"PUT",
			"path":"/object/" + object.id,
			"body":object
		},
		response:{
			"body":object
		}
	},
	{
		"name":"获取对象列表",
		"request":{
			"path":"/object",
			"query":listArgs
		},
		"response":{
			"headers":{
				"Total-Objects":1000,//去除分页参数后的对象数目
			},
			"body":[
				object
			]
		}
	},
	
	/*
	 * 对象属性的CURD
	 */
	
	/*
	 * meta
	 */
	{
		"name":"为一个对象添加一个元数据",
		"request":{
			"method":"POST",
			"path":"/object/" + object.id + "/meta/" + meta.key,
			"query":{},
			"body":value//without "id" attribute
		},
		"response":{
			"body":meta//all meta key-values of the object
		}
	},
	{
		"name":"更新对象的一个元数据",
		"request":{
			"method":"PUT",
			"path":"/object/" + object.id + "/meta/" + meta.key,
			"query":{},
			"body":value
		}
	},
	{
		"name":"删除一个对象的一个元数据",
		"request":{
			"method":" DELETE",
			"path":"/object/" + object.id + "/meta/" + meta.key,
			"query":{},
			"query":meta//with "id" or some attributes for resource locating
		}
	},
	{
		"name":"推荐的meta.name",
		"request":{
			"method":"GET",
			"path":"meta/name",
			"query":{
				"object":0,
				"type":""
			}
		},
		"response":[
			"电话","地址","邮件"
		]
	},
	
	/*
	 * status
	 */
	{
		"name":"为一个对象添加一个状态",
		"request":{
			"method":"POST",
			"path":"/object/" + object.id + "/status/" + status.name,
			"query":{},
			"body":status//without "id" attribute
		},
		"response":{
			"body":status
		}
	},
	{
		"name":"更新对象的一个状态",
		"request":{
			"method":"PUT",
			"path":"/object/" + object.id + "/status/" + status.name,
			"query":{},
			"body":status
		}
	},
	{
		"name":"删除一个对象的一个状态",
		"request":{
			"method":" DELETE",
			"path":"/object/" + object.id + "/status/" + status.name,
			"query":{},
		}
	},
	{
		"name":"推荐的status.name",
		"request":{
			"method":"GET",
			"path":"status/name",
			"query":{
				"object":0,
				"type":""
			}
		},
		"response":[
			"立案","签约","一审开庭"
		]
	},
	
	/*
	 * relative
	 */
	{
		"name":"为一个对象添加或更新一个相关对象",
		"request":{
			"method":"POST",
			"path":"/object/" + object.id + "/relative/" + relative.role,
			"query":{},
			"body":object.id//without "id","name" and "type" attribute
		},
		"response":relative

	},
	{
		"name":"删除一个对象的一个相关对象",
		"request":{
			"method":" DELETE",
			"path":"/object/" + object.id + "/relative/" + relative.role,
			"query":{}
		}
	},
	{
		"name":"推荐的relative.relation",
		"request":{
			"method":"GET",
			"path":"relative/relation",
			"query":{
				"object":0,
				"type":"",
				"relative":0,
				"relative_type":""
			}
		},
		"response":[
			"主办律师","协办律师","案源人"
		]
	},
	
	/*
	 * tag
	 */
	{
		"name":"为一个对象设置标签",
		"request":{
			"method":"POST",
			"path":"/object/" + object.id + "/tag/" + tag.taxonomy,
			"body":"tag" | ["tags"]
		},
		"response":{
			"body":tag
		}

	},
	{
		"name":"推荐的tag.name",
		"request":{
			"method":"GET",
			"path":"tag/name",
			"query":{
				"object":0,
				"type":"",
			}
		},
		"response":[
			"潜在客户","成交客户"
		]
	},
	
	/**
	 * permission
	 */
	{
		"name":"为一个对象设置权限"
	},
	
	
	/*
	 * 其他api
	 */
	{
		"name":"用户登录",
		"request":{
			"method":"GET/POST",
			"path":"/login",
			"query/body":{
				"username":"",
				"password":"",//开发阶段，明文
				"remember":FALSE,
			}
		}
	},
	{
		"name":"用户登出",
		"request":{
			"method":"GET",
			"path":"/logout"
		}
	},
	{
		"name":"页面框架",
		"request":{
			"path":"/"
		}
	},
	{
		"name":"导航菜单",
		"request":{
			"path":"/nav",
		},
		"response":{
			"header":{
				"contentType":"application/json"
			},
			"body":[
				{
					"id":1,
					"name":"潜在客户",//菜单的显示名称
					"params":{//由前端自行决定，json存数据库
						"controller":"List",
						"type":"客户",
						"tag":["潜在客户"]
					},
					"parent":0
				}
			]
		}
	},
	{
		"name":"菜单存储",
		"request":{
			"method":"PUT/POST",
			"path":"/nav",
			"body":[
				{
					"name":"潜在客户",//菜单的显示名称
					"params":{//由前端自行决定，json存数据库
						"controller":"List",
						"type":"客户",
						"tag":["潜在客户"]
					},
					"parent":0
				}
			]
		}
	},
	{
		"name":"菜单更新",
		"request":{
			"method":"POST",
			"path":"/nav",
			"query":{
				"id":1
			},
			"body":[
				{
					"name":"潜在客户",//菜单的显示名称
					"params":{//由前端自行决定，json存数据库
						"controller":"List",
						"type":"客户",
						"tag":["潜在客户"]
					},
					"parent":0
				}
			]
		}
	},
	{
		"name":"菜单删除",
		"request":{
			"method":"DELETE",
			"path":"/nav",
			"query":{
				"id":1
			}
		}
	}
];

/**
 * 对象列表搜索参数
 */
var listArgs = "{value}"/*直接匹配一个值，默认匹配的键为object.id*/ | ["{value}"]/*作为{in:[]}处理*/ | {
	"search":"",//智能搜索
	"id":0,//根据id获得对象
	"name":listArgs,//usage: {"name":"Jason"}, {"name":["Jason","Mike","Marry"]}, {"name":{"in":["Jason","Mike","Marry"]}}
	"type":listArgs,
	"num":listArgs,
	"time":listArgs,
	"user":listArgs,
	"meta":{"{key}": listArgs} | [listArgs],//前一种按键-值对搜索，后一种按键名
	"status":{"{name}": listArgs} | [listArgs],//前一种按状态-时间对搜索，后一种按状态名
	"tag":{"{taxonomy}": listArgs} | [listArgs],//前一种按分类方式-分类搜索，后一种按分类
	"is_relative_of":{"{role}": listArgs} | [listArgs],//前一种按关系-关联对象搜索，后一种按关联对象
	"has_relative_like":{"{role}": listArgs} | [listArgs],//前一种按关系-关联对象搜索，后一种按关联对象
	"and, or":listArgs,//逻辑运算，如{"or":{"name":{"in":["A", "B"]}, "name":{"nin":["C","D"]}}。
	"gt, gte, lt, lte, ne":"{value}",//算数运算
	"in, nin":["{value}"],//in 或 not in

	"with_meta":false | getMetaArgs,
	"with_relative":false | getRelativeArgs,
	"with_status":false | getStatusArgs,
	"with_tag":false | getTagArgs,

	"order_by":[//支持2种格式
		"id desc",
		[
			["id","desc"],
			["name","asc"]
		]
	],
	
	//根据页码和每页行数分页
	"page":1,
	"per_page":25,
	
	//根据需要的行数和偏移行数分页
	"limit":[//支持2种格式
		10,//直接给出需要的行数,
		[10,20]//需要的行数, 起点偏移
	]
};

var getMetaArgs = {
	as_rows: false
}

var getStatusArgs = {
	as_rows: false,
	id_only: false,
	include_disabled: false,
	with_meta: true
}