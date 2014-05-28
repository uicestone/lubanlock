/**
 * data structure definition
 * /

/**
 *  对象数据结构
 */
var object = {
	"id":"",
	"type":"",//类型，如"人员", "联系人", "案件", "事务"
	"num":"",//编号，字符串
	"name":"",//显示名称
	"meta":meta,
	"relative":relative,
	"status":status,
	"tag":tag,
	"permission":permission,
	"user":user.id,
	"time":"1970-01-01 00:00:00",
	"time_insert":"1970-01-01 00:00:00"
};

/*
 * 元数据
 * 存放对象的普通属性，如人员的电话，案件的简介，考试的参与人数、班级的教室等
 */
var meta = {
	"{key}": ["{value1}", "{value2}"]
} | [metaRow];

var metaRow = {
	"key":"",
	"value":""
}

/**
 * 关联对象
 * 描述与本对象关联的对象的简要信息，以及关系
 */
var relative = {
	"{relation}": [
		{
			"id": object.id,
			"type":object.type,//关联对象的object.type
			"name": object.name,//关联对象的object.name
			"num":object.num,//关联对象的object.num
			"relationship_num":"",//关系的编号
			meta:{//关系的元数据
				"{key}":"{value}"
			}
		}
	]
} | {
	"{relation}": [object.id]
};

/*
 * 一个对象的各种与时间有关的状态
 * 如，案件的立案时间，帐目的应收帐款时间，日程的底线时间
 */
var status = {
	"{name}":"{date}"
} | [statusRow];

var statusRow = {
	"name":"",
	"date":"1970-01-01 00:00:00",
	"comment":""
}

/*
 * 一个对象的标签，用于搜索和分类
 */
var tag = {
	"{taxonomy}": ["{term1}","{term2}"]
};

var permission = {
	"read":[user.id],
	"write":[user.id],
	"grant":[user.id]
}

var user = {
	"roles":[],
	"email":""
} + object;

var nav = [navItem];

var navItem = {
	"id":"",
	"name":"",
	"template":"",
	"params":{
		
	},
	"order":0,
	"subnav":navItem
}

/**
 * server api
 */
var api = {
	/*整个对象的CURD*/
	"获取单个对象":{
		"request":{
			"method":"GET",
			"path":"/object/" + object.id,
			"query":args.getObjectProperty
		},
		"response":{
			"contentType":"application/json",/*contentType默认都为application/json*/
			"body":object
		}
	},
	"创建单个对象":{
		"request":{
			"method":"POST",
			"path":"/object",
			"body":object//without id, uid, time, timeinsert attributes
		},
		response:{
			"body":object
		}
	},
	"更新单个对象":{
		"request":{
			"method":"PUT",
			"path":"/object/" + object.id,
			"body":object
		},
		response:{
			"body":object
		}
	},
	"获取对象列表":{
		"request":{
			"path":"/object",
			"query":args.getList + args.objectField + args.algorithm + args.logical
		},
		"response":{
			"headers":{
				"Status-Text":""// 一个经过JSON编码的HTTP StatusText字符串
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
	"为一个对象添加一个元数据":{
		"request":{
			"method":"POST",
			"path":"/object/" + object.id + "/meta/" + metaRow.key,
			"query":{},
			"body":value//without "id" attribute
		},
		"response":{
			"body":meta//all meta key-values of the object
		}
	},
	"更新对象的一个元数据":{
		"request":{
			"method":"PUT",
			"path":"/object/" + object.id + "/meta/" + metaRow.key,
			"query":{},
			"body":value
		}
	},
	"删除一个对象的一个元数据":{
		"request":{
			"method":" DELETE",
			"path":"/object/" + object.id + "/meta/" + metaRow.key,
			"query":{"value":""}// 指定要删除的metaRow.value
		}
	},
	"推荐的meta.name":{
		// 暂未实现
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
	"为一个对象添加一个状态":{
		"request":{
			"method":"POST",
			"path":"/object/" + object.id + "/status/" + statusRow.name,
			"query":{},
			"body":status//without "id" attribute
		},
		"response":{
			"body":status
		}
	},
	"更新对象的一个状态":{
		"request":{
			"method":"PUT",
			"path":"/object/" + object.id + "/status/" + statusRow.name,
			"query":{},
			"body":status
		}
	},
	"删除一个对象的一个状态":{
		"request":{
			"method":" DELETE",
			"path":"/object/" + object.id + "/status/" + statusRow.name,
			"query":{}
		}
	},
	"推荐的状态名称":{
		// 暂未实现
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
	"为一个对象添加或更新一个相关对象":{
		"request":{
			"method":"POST",
			"path":"/object/" + object.id + "/relative/" + relative.role,
			"query":{},
			"body":object.id//without "id","name" and "type" attribute
		},
		"response":relative

	},
	"删除一个对象的一个相关对象":{
		"request":{
			"method":" DELETE",
			"path":"/object/" + object.id + "/relative/" + relative.role,
			"query":{}
		}
	},
	"推荐的relative.relation":{
		// 暂未实现
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
	"为一个对象设置标签":{
		"request":{
			"method":"POST",
			"path":"/object/" + object.id + "/tag/" + tag.taxonomy,
			"body":"tag" | ["tags"]
		},
		"response":{
			"body":tag
		}

	},
	"推荐的tag.name":{
		"request":{
			"method":"GET",
			"path":"tag/name",
			"query":{
				"object":0,
				"type":""
			}
		},
		"response":[
			"潜在客户","成交客户"
		]
	},
	
	/**
	 * permission
	 */
	"为一个对象设置权限":{
		"request":{
			"method":"POST",
			"path":"/object/" + object.id + ("/authorize" | "/prohibit") + ("/read" | "/write" | "/grant"),
			"body":user.id | [user.id]
		}
	},
	
	
	/*
	 * 其他api
	 */
	"用户登录":{
		"request":{
			"method":"GET/POST",
			"path":"/login",
			"query/body":{
				"username":"",
				"password":"",//开发阶段，明文
				"remember":FALSE
			}
		}
	},
	"用户登出":{
		"request":{
			"method":"GET",
			"path":"/logout"
		}
	},
	"页面框架":{
		"request":{
			"path":"/"
		}
	},
	"导航菜单":{
		"request":{
			"path":"/nav"
		},
		"response":{
			"body":[
				{
					"id":"",
					"name":"潜在客户",//菜单的显示名称
					"template":"",
					"params":{
						"type":"客户",
						"tag":["潜在客户"]
					},
					"parent":""
				}
			]
		}
	},
	"菜单存储":{
		"request":{
			"method":"POST",
			"path":"/nav",
			"body":[
				{
					"name":"潜在客户",//菜单的显示名称
					"params":{//由前端自行决定，json存数据库
						"controller":"List",
						"type":"客户",
						"tag":["潜在客户"]
					},
					"parent":""
				}
			]
		}
	},
	"菜单更新":{
		"request":{
			"method":"PUT",
			"path":"/nav",
			"query":{
				"name":""
			},
			"body":[
				{
					"name":"潜在客户",//菜单的显示名称
					"params":{//由前端自行决定，json存数据库
						"controller":"List",
						"type":"客户",
						"tag":["潜在客户"]
					},
					"parent":""
				}
			]
		}
	},
	"菜单删除":{
		"request":{
			"method":"DELETE",
			"path":"/nav",
			"query":{
				"name":""
			}
		}
	}
};

/**
 * API中涉及的所有通用的参数
 */
var args = {};

/**
 * 获得列表的基本参数，这些参数是不具有递归属性的
 */
args.getList = {
	"search":"",//智能搜索

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

/**
 * 获取单个对象或对象列表时，指定是否获取这些对象的扩展属性
 * 对于获取单个对象，这些属性默认都是获取的，在对象列表中，默认都是不获取的
 * 不仅可以指定true|false，还可以接受参数，来控制获得属性的行为，如返回属性的格式
 */
args.getObjectProperty = {
	"with_meta":true | args.getMeta,
	"with_relative":true | args.getRelative,
	"with_status":true | args.getStatus,
	"with_tag":true | args.getTag,
	"with_permission":true | args.getPermission,
	"with":["meta","relative","status","tag","permission"] | {
		"meta": true | args.getMeta,
		"relative": true | args.getRelative,
		"status": true | args.getStatus,
		"tag": true | args.getTag,
		"permission": true | args.getPermission
	}
}

/**
 * 对象字段参数
 */
args.objectField = {
	"id": args.algorithm + args.logical,
	"name": args.algorithm + args.logical,//usage: {"name":"Jason"}, {"name":["Jason","Mike","Marry"]}, {"name":{"in":["Jason","Mike","Marry"]}}
	"type": args.algorithm + args.logical,
	"num": args.algorithm + args.logical,
	"time": args.algorithm + args.logical,
	"user": args.algorithm + args.logical,
	"meta":{"{key}": args.algorithm + args.logical} | [args.algorithm],//前一种按键-值对搜索，后一种按键名
	"status":{"{name}": args.algorithm + args.logical} | [args.algorithm],//前一种按状态-时间对搜索，后一种按状态名
	"tag":{"{taxonomy}": args.algorithm + args.logical} | [args.algorithm],//前一种按分类方式-分类搜索，后一种按分类
	"is_relative_of":{"{role}": args.objectField} | [args.objectField],//前一种按关系-关联对象搜索，后一种按关联对象
	"has_relative_like":{"{role}": args.objectField} | [args.objectField]//前一种按关系-关联对象搜索，后一种按关联对象
}

/**
 * 逻辑运算参数
 */
args.logical = {
	"and": args.objectField,//逻辑运算，如{"or":{"name":{"in":["A", "B"]}, "name":{"nin":["C","D"]}}。
	"or": args.objectField
};

/**
 * 算数比较运算参数
 */
args.algorithm = "{value}"/*直接匹配一个值，默认匹配的键为object.id*/ | ["{value}"]/*作为{in:[]}处理*/ | {
	"gt":"{value}",
	"gte":"{value}",
	"lt":"{value}",
	"lte":"{value}",
	"ne":"{value}",
	"in":["{value}"],
	"nin":["{value}"]
};

args.getMeta = {
	as_rows: false
}

args.getStatus = {
	as_rows: false
}

args.getRelative = {
	as_rows: false,
	id_only: false,
	include_disabled: false,
	with_meta: true
}
