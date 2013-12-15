/**
 * server api
 */
var api = [
	/*整个对象的CURD*/
	{
		"name":"获取单个对象",
		"request":{
			"method":"GET",
			"path":"/object/{id}",
			/*query指url中的query string，也可以用?query={json}来传递*/
			"query":{
				"with_meta":true,
				"with_relative":true,
				"with_status":true,
				"with_tag":true
			}
		},
		"response":{
			"contentType":"application/json",/*contentType默认都为application/json*/
			"body":objectData
		}
	},
	{
		"name":"更新单个对象",
		"request":{
			"method":"POST",
			"path":"/object/{id}",
			"contentType":"application/json",/*PUT和POST的contentType默认都为application/json*/
			"body":objectData
		},
		response:{
			"body":objectData
		}
	},
	{
		"name":"创建单个对象",
		"request":{
			"method":"PUT/POST",
			"path":"/object",
			"body":objectData//without id, uid, time, timeinsert attributes
		},
		response:{
			"body":objectData
		}
	},
	{
		"name":"获取对象列表",
		"request":{
			"path":"/object",
			"query":listArgs
		},
		"response":{
			"body":{
				"total":1000,//去除分页参数后的对象数目
				"data":[
					objectData
				]
			}
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
			"method":"PUT/POST",
			"path":"/object/{id}/meta",
			"body":meta//without "id" attribute
		},
		"response":{
			"body":meta
		}
	},
	{
		"name":"更新对象的一个元数据",
		"request":{
			"method":"POST",
			"path":"/object/{id}/meta",
			"body":meta
		}
	},
	{
		"name":"删除一个对象的一个元数据",
		"request":{
			"method":" DELETE",
			"path":"/object/{id}/meta",
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
			"method":"PUT/POST",
			"path":"/object/{id}/status",
			"body":status//without "id" attribute
		},
		"response":{
			"body":status
		}
	},
	{
		"name":"更新对象的一个状态",
		"request":{
			"method":"POST",
			"path":"/object/{id}/status",
			"body":status
		}
	},
	{
		"name":"删除一个对象的一个状态",
		"request":{
			"method":" DELETE",
			"path":"/object/{id}/status",
			"query":status//with "id" or some attributes for resource locating
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
		"name":"为一个对象添加一个相关对象",
		"request":{
			"method":"PUT/POST",
			"path":"/object/{id}/relative",
			"body":relative//without "id","name" and "type" attribute
		},
		"response":relative

	},
	{
		"name":"更新对象的一个相关对象",
		"request":{
			"method":"POST",
			"path":"/object/{id}/relative",
			"body":relative//without "name" and "type" attribute
		}
	},
	{
		"name":"删除一个对象的一个相关对象",
		"request":{
			"method":" DELETE",
			"path":"/object/{id}/relative",
			"query":relative//with "id" or some attributes for resource locating
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
	{
		"name":"为一个对象和另一个对象的关系添加一个状态(设为肯定)",
		"request":{
			"method":"PUT/POST",
			"path":"/object/{id}/relativemod",
			"query":{
				"id":0,//关系的id
				"relative":0//关联对象的id
			},
			"body":{
				name:"",//开关量的名称
			}
		}
	},
	{
		"name":"删除一个对象和另一个对象的关系的状态(设为否定)",
		"request":{
			"method":" DELETE",
			"path":"/object/{id}/relativemod",
			"query":{
				"id":0,//关系的id
				"relative":0,//关联对象的id
				name:"",//开关量的名称
			}
		}
	},
	
	/*
	 * tag
	 */
	{
		"name":"为一个对象添加一个标签",
		"request":{
			"method":"PUT/POST",
			"path":"/object/{id}/tag",
			"body":tag//with "name" or "id"
		},
		"response":{
			"body":tag
		}

	},
	{
		"name":"更新对象的一个标签",
		"request":{
			"method":"POST",
			"path":"/object/{id}/tag",
			"body":tag
		}
	},
	{
		"name":"删除一个对象的一个标签",
		"request":{
			"method":" DELETE",
			"path":"/object/{id}/tag",
			"query":tag//with "id" or some attributes for resource locating
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
 * 对象数据结构
 */
var objectData={
	"id":0,
	"type":"",//对象类型如"人员", "联系人", "案件", "事务"
	"num":"",//对象的编号，字符串
	"name":"",//对象的显示名称
	"{additional_fields}":"",//非必有，根据不同type的对象，可能有些额外的根字段（考虑一律去处这些字段）
	"meta":[//非必有，获得对象时with_meta参数决定
		meta
	],
	"relative":[//非必有，获得对象时with_relative参数决定
		relative
	],
	"status":[//非必有，获得对象时with_status参数决定
		status
	],
	"tag":[//非必有，获得对象时with_tag参数决定
		tag
	]
};

/*
 * 元数据
 * 存放对象的普通属性，如人员的电话，案件的简介，考试的参与人数、班级的教室等
 */
var meta={
	"id":0,
	"name":"",
	"content":"",
	"comment":""
};

/*
 * 开关量
 * 在数据库中以整数存放，转换成二进制后每一位表示一个开关量
 * 开关量的名称在后端定义，因此只有“是”和“否”，并没有“未知”，因此只有增删，没有改
 * 对象有开关量，对象与对象的关联也有开关量
 * 如一个对象的读, 写权限, 又如一个日程对于某用户的“删除”状态
 */
var mod=["read","write"];

/**
 * 关连对象
 * 描述与本对象关联的对象的简要信息，以及关系
 */
var relative={
	"id":0,//关系id
	"num":"",//关系编号，比如一个学生在一个班级中的学号
	"relation":"",
	"mod":mod,//一组可变权限/状态名
	"weight":0.00,//比重，同relation的比重之和不应超过1
	"relative":0,//关联对象id
	"type":"",//关连对象类型
	"name":""//关连对象显示名称
};

/*
 * 一个对象的各种与时间有关的状态
 * 如，案件的立案时间，帐目的应收帐款时间，日程的底线时间
 */
var status={
	"id":0,
	"name":"",
	"type":"",
	"datetime":"",
	"content":"",
	"comment":""
};

/*
 * 一个对象的标签，用于搜索和分类
 */
var tag={
	"id":0,//这是tag的id，而不是object-tag的关系id
	"name":"",
	"type":"",//标签类型，分类的分类，如“阶段”，“领域”,
	"color":"#000"
};

/**
 * 对象列表搜索参数
 */
var listArgs={
	"search":"",
	"id_in":[],
	"id_less_than":0,
	"id_greater_than":0,
	"name":"",//模糊匹配
	"type":"",//对象类别
	"num":"",//模糊匹配
	"display":true,
	//"company":0,
	"uid":0,//对象的创建人

	"tags":{"tagType":tagName,"0":tagName},//包含一组标签
	"without_tags":{"tagType":tagName,"0":tagName},//不包含一组标签
	"with_tags":false,

	"meta":{"metaName":metaContent,"0":metaName},//包含一组资料项，一组资料项为某值
	"with_meta":false,

	"is_relative_of":0,//根据本对象获得相关对象
		"is_relative_of__relation":"",//只查找具有某关系的相关人
	"has_relative_like":0,//根据相关对象获得本对象
		"has_relative_like__relation":"",
	"is_secondary_relative_of":0,//右侧相关对象的右侧相关对象，“下属的下属”
		"is_secondary_relative_of__media":"",//中间对象的type
	"is_both_relative_with":0,//右侧相关对象的左侧相关对象，“具有共同上司的同事”
		"is_both_relative_with__media":"",
	"has_common_relative_with":0,//左侧相关对象的右侧相关对象，“具有共同下属的上司”
		"has_common_relative_with__media":"",
	"has_secondary_relative_like":0,//左侧相关对象的左侧相关对象，“上司的上司”
		"has_secondary_relative_like__media":"",
	"with_relative":false,

	"status":{
		"name":[//存在两种判断方式
			{"from":"from_syntax","to":"to_syntax","format":"timestamp/date/datetime"},
			false//bool，仅过滤出包含或不包含该状态的对象
		],
		//例子
		"首次接洽":{"from":1300000000,"to":1300100000,"format":"timestamp"},
		"立案":{"from":"2013-01-01","to":"2013-06-30"},
		"结案":true
	},
	"with_status":false,

	"orderby":[//支持2种数据格式
		"id desc, name asc",
		[
			["id","desc"],
			["name","asc"]
		]
	],
	"limit":[//支持2种数据格式
		10,//直接给出需要的行数,
		[10,20]//需要的行数, 起点偏移
	]
};