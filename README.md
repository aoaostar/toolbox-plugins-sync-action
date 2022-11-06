## 同步插件到`傲星工具箱云端`

使用方法参考：[.github/workflows/push.yml](.github/workflows/push.yml)

```yaml
- name: Get Config
  run:
    cat > config.ini <<EOF

    ${{ secrets.TOOL_CLOUD_CONFIG }}

    EOF

- name: Sync Action
  uses: ./
  with:
    config_path: ${{ github.workspace }}/config.ini
    plugin_path: ${{ github.workspace }}/src
```

### config.ini
```ini
[tool-cloud]
username = aoaostar
link = https://tool.aoaostar.com/{alias}
category_id = 0
token = xxx

[github]
owner = aoaostar
repo = toolbox-plugins
branch = master
path = dist/{space}/{alias}.zip
```