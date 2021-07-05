# wlog: 一个基于monolog封装的日志类工具

### 功能扩展：

1：日志初始化配置（目录、默认通道、每天默认日志大小、登录用户id设置)  
2：日期自动切割  
3：根据日志级别生成日志  
4：日志记录有进程id，用以查看一次请求的所有日志信息  
5：日志记录有唯一标识符

### 日志示例

##### 1: info级别日志

`{"message":"time:2021-06-30 17:37:52###userId:3604###msg:1开票统计报表###trace:[3604][/home/web/stat/controllers/StatInvoiceController.php:42] \n\n","context":[{"name":"lyk"},{"code":"1开票统计报表"}],"level":200,"level_name":"INFO","channel":"1invoice","datetime":"2021-06-30T17:37:52.047481+08:00","extra":{"url":"/api/stat/invoice?pay_type=1&product_date_start=2021-03-01&product_date_end=2021-06-09&load=0","ip":"111.198.71.156","http_method":"GET","server":"xxx.com","referrer":null,"process_id":29306,"uid":"ee903b3"}}`

##### 2：sql日志

`{"message":"time:2021-07-01 14:55:16###userId:6284###msg:SQL:SELECT s.scope_city,i.unit_id,i.self_price,i.currency_id,i.bill_id,b.bill_code,b.invoice_status,u.name,u.join_type from financial_item as i left join stat_zhongtai_box as s on i.box_id=s.box_id left join financial_bill as b on i.bill_id=b.id left join financial_unit as u on i.unit_id=u.id  where s.box_id>0 and (b.from_business=1 or b.from_business is null) AND s.id>=52292 AND s.min_product_time >= '2021-03-01 00:00:00' and s.min_product_time <= '2021-06-09 23:59:59' AND i.pay_type = 1 0.090804100036621###trace: \n\n","context":{},"level":100,"level_name":"DEBUG","channel":"default","datetime":"2021-07-01T14:55:16.898858+08:00","extra":{"url":"/api/stat/invoice?pay_type=1&product_date_start=2021-03-01&product_date_end=2021-06-09&load=0","ip":"111.198.71.156","http_method":"GET","server":"xxx.com","referrer":null,"process_id":29462,"uid":"f0a7f0a"}}`