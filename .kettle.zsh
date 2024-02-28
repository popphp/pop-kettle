function _kettle_add_completion() {
  compadd `_kettle_get_command_list`
}

function _kettle_get_command_list() {
	php kettle help --raw | sed "s/    .\/kettle //g" | sed "s/[[:space:]].*//g"
}

compdef _kettle_add_completion kettle
