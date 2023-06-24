default:
	source .venv/bin/activate; \
	python src/run.py

test:
	source .venv/bin/activate; \
	pytest -vv

install:
	source ./venv/bin/activate; \
    rye sync