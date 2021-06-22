.PHONY: default
default: build_dev

.PHONY: build_dev
build_dev:
	npx webpack

.PHONY: watch
watch: watch_dev

.PHONY: watch_dev
watch_dev:
	npx webpack --watch

