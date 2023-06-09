PROFILE = src/personal

default:
	source .venv/bin/activate; \
	python src/run.py --profile ${PROFILE}

test:
	source .venv/bin/activate; \
	pytest -vv

install:
	source ./venv/bin/activate; \
    pip install -r requirements.txt