
TARGETS = runguard

build: $(TARGETS)

runguard.build: %.build: %.c ../../config/config.h
	g++ -O2 $< -o $@
	strip $@

clean:
	-rm -f $(TARGETS)

suid:
	chown root $(TARGETS)
	chmod 4755 $(TARGETS)
