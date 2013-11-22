VERSION=2.4
BUILDDATE=$(shell date  +%Y-%m-%d-%H-%M-%S)
CONFPATH=../../etc/system-msg.conf
pCONFPATH=$(shell echo "$(CONFPATH)" | sed -e 's/[]\/()$*.^|[]/\\&/g')
all: clean build
build:
	@echo "Building $(VERSION) on $(BUILDDATE)";
	@mkdir build;
	@cp source/system-msg build/system-msg;
	@sed -i 's/\$$VERSION/$(VERSION)/g' build/system-msg;
	@sed -i 's/\$$BUILDDATE/$(BUILDDATE)/g' build/system-msg;
	@sed -i 's/\$$CONFPATH/$(pCONFPATH)/g' build/system-msg;
install:
	@echo "Installing";
	@cp build/system-msg /usr/bin/system-msg;
	@chmod +x /usr/bin/system-msg;
	@cp source/line /usr/bin/line;
	@chmod +x /usr/bin/line;
	@cp source/system-msg.conf /etc/system-msg.conf;
	@mkdir -p /var/cache/system-msg;
clean:
	@rm -rf build;