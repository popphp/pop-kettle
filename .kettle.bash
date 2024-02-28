#/usr/bin/env bash
_kettle () {
  COMP_WORDBREAKS=${COMP_WORDBREAKS//:}
  COMMANDS=`php kettle help --raw | sed "s/    .\/kettle //g" | sed "s/[[:space:]].*//g"`
  COMPREPLY=(`compgen -W "$COMMANDS" -- "${COMP_WORDS[COMP_CWORD]}"`)
  return 0
}

complete -F _kettle kettle
