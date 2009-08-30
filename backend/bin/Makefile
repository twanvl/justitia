
CXXFLAGS=-O2

TARGETS = runguard

build: $(TARGETS)

runguard.build: %.build: %.c ../../config/config.h
	$(CXX) $(CXXFLAGS) $< -o $@
	$(STRIP) $@

clean:
	-rm -f $(TARGETS)

suid:
	chown root $(TARGETS)
	chmod 4755 $(TARGETS)
