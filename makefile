default:
	python src/run.py

test:
	pytest -vv

install:
	rye sync

