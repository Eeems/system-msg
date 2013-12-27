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
	@mkdir -p $(DESTDIR)/usr/bin;
	@cp build/system-msg $(DESTDIR)/usr/bin/system-msg;
	@chmod +x $(DESTDIR)/usr/bin/system-msg;
	@cp source/line $(DESTDIR)/usr/bin/line;
	@chmod +x $(DESTDIR)/usr/bin/line;
	@mkdir -p $(DESTDIR)/etc;
	@cp source/system-msg.conf $(DESTDIR)/etc/system-msg.conf;
	@mkdir -p $(DESTDIR)/var/cache/system-msg;
clean:
	@rm -rf build;