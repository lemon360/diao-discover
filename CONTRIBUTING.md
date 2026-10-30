# 参与贡献

欢迎提交 Bug、可访问性改进、兼容性修复与功能建议。提交前请搜索已有 Issue，并写明主题版本、WordPress/PHP 版本、复现步骤及预期结果。

开发环境将本仓库放入 `wp-content/themes/diao-discover`，在独立 WordPress 测试站启用；无需 Node.js 或前端构建。使用 WordPress Settings API、权限和 nonce 校验、参数化查询，以及与输出上下文匹配的转义。

提交修改前对 PHP 文件执行 `php -l`、对 `assets/app.js` 执行 `node --check`；对行为改动在测试站验证管理员与游客访问，并覆盖失败路径。界面修改请检查浅色、深色、桌面、手机和键盘操作。数据库或邮件改动需验证升级、去重和失败重试。

请勿提交 WordPress 核心、wp-config.php、数据库、邮件记录、SMTP 密码、统计服务私钥、用户邮箱、node_modules 或本地测试账号。贡献代码按本仓库 GPL-2.0-or-later 许可发布。
