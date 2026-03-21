# H5小游戏库 - Docker部署

## 1Panel 部署步骤

### 方式一：使用 1Panel 构建（推荐）

1. **上传文件**：将整个 `27h5` 目录上传到服务器
2. **解压游戏源码**：
   ```bash
   cd /path/to/27h5
   unzip game.zip
   mv game/www/wwwroot/game/* game/
   rm -rf game/www
   ```
3. **创建游戏目录**（如果不存在）：
   ```bash
   mkdir -p game
   ```
4. **在 1Panel 中操作**：
   - 进入 **容器** → **编排** → **创建编排**
   - 填写名称：`h5game`
   - 编排文件：直接粘贴下方内容，或上传 docker-compose.yml
   - 构建镜像：上传 Dockerfile，填写构建参数
   - 端口映射：8080:80

### 方式二：命令行部署

```bash
cd /path/to/27h5

# 1. 解压游戏
unzip game.zip
mv game/www/wwwroot/game/* game/
rm -rf game/www

# 2. 构建镜像
docker-compose build

# 3. 启动容器
docker-compose up -d
```

## 访问

- 地址：`http://你的服务器IP:8080`
- 聊天室数据库：`game/chat/chat.db`

## 目录结构

```
27h5/
├── Dockerfile          # 镜像构建文件
├── docker-compose.yml # 容器编排
├── nginx.conf         # Nginx配置
├── supervisord.conf   # 进程管理
├── game/              # 游戏文件（需解压后放入）
└── game.zip           # 游戏源码
```

## 技术栈

- Alpine 3.19
- Nginx
- PHP 8.2
- Supervisord
