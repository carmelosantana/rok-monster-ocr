#!/usr/bin/env bash
set -eoux pipefail
JOB=${1:-"governor-more-info-kills"}
php rok.php --job="${JOB}" --input_path="media/" --tessdata="tessdata/" "${@:2}"