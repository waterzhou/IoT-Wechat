
Develop it based on below project:

https://github.com/dodgepudding/wechat-php-sdk

File list
==============
1. config.php       	定义项目所用到的全局参数
2. wechat.class.php 	基础公众号SDK
3. wx_iot.class.php 	微信硬件的SDK
4. errCode.php      	错误码解释
5. index.php        	后台工具的入口程序
6. phoqrcode        	第三方二维码插件，完全不依赖外界的独立php插件
7. qrcode.png       	二维码生成临时图像文件
8. cloud_callback.php   第三方云的后台入口
10. DbTest.php			数据库测试程序
11. IoT-Wechat.zip		上传SAE云的临时打包文件
12. README.MD			说明文件
13. app_smdzjl.sql 		部署前，动态导出本项目MySql/bxxh的*.sql文件，并导入到第三方云上
14. tool.php            测试工具角色，可以写测试脚本，进行在线测试
15. tool.create.menu.php后台强制创建菜单
16. tool.qrcode.gen.php 受控index.php页面的二维码自动生成程序
17. tool.dev.bind.php   后台强制绑定用户
18. tool.dev.bind.php   后台强制解绑用户v
19. tool.pageshare.php  测试页面共享以及JSSDK功能
20. wx_jssdk.php        JSSDK类封装
21. tool.ajxhisdata.php 用于被tool.pageshare.php调用的后台历史数据访问php程序


How to deploy this project：
1. 配置Config.php运行环境参数
2. 打包本项目成*.zip文件
3. 上传到SAE云
4. 导出导入MySql数据库
5. 在微信接入第三方云界面，确认微信接口URL的Token验证通过
6. 在后台管理TOOL界面，确认API接口Token刷新机制工作正常

