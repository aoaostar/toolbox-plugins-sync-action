if [ ! $CONFIG_PATH ] || [ ! $PLUGIN_PATH ];then
  echo "CONFIG_PATH 或者 PLUGIN_PATH 不得为空"
	exit 1
fi

\cp -rf $CONFIG_PATH /action/config.ini
\cp -rf $PLUGIN_PATH/* /action/src/plugin

php /action/main.php