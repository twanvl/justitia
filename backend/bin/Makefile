
TARGETS = runguard

build: $(TARGETS)

runguard: runguard.c ../../config/config.h
	gcc -O2 $< -o $@
	strip $@

clean:
	-rm -f $(TARGETS)

suid:
	chown root $(TARGETS)
	chmod 4755 $(TARGETS)
