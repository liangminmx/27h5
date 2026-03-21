# H5小游戏库 - Docker部署

## 快速部署

### 方式一：从 GitHub Packages 拉取镜像（推荐）

1. **创建 Release 上传游戏文件**：
   - 在 GitHub 仓库页面点击 **Releases** → **Create a new release**
   - 标题填 `game`
   - 将 `game.zip` 作为附件上传
   - 发布 Release

2. **GitHub Actions 自动构建镜像**（构建完成后会出现在 Packages）

3. **1Panel 部署**：
   ```yaml
   # docker-compose.yml
   services:
     h5game:
       image: ghcr.io/liangminmx/h5game:latest
       container_name: h5game
       restart: always
       ports:
         - "8080:80"
   ```

4. **拉取镜像并运行**：
   ```bash
   docker pull ghcr.io/liangminmx/h5game:latest
   docker run -d --name h5game -p 8080:80 ghcr.io/liangminmx/h5game:latest
   ```

### 方式二：本地构建镜像

1. **上传文件到服务器**
2. **解压游戏**：
   ```bash
   unzip game.zip
   mv game/www/wwwroot/game/* game/
   rm -rf game/www
   ```
3. **构建并启动**：
   ```bash
   docker-compose up -d
   ```

## 访问

- 地址：`http://你的服务器IP:8080`

## 技术栈

- Alpine 3.19
- Nginx
- PHP 8.2
- Supervisord

## 镜像地址

```
ghcr.io/liangminmx/h5game:latest
```
