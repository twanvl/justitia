
TARGETS = runguard

# Some configuration of programs that need set-uid root permissions:
SUIDTARGETS=runguard
SUIDBUILDS=$(addsuffix .build,$(SUIDTARGETS))

build: $(TARGETS)

install: build install-setuid

$(SUIDTARGETS): %: %.build

runguard.build: %.build: %.c ../../config/config.h
	$(CXX) $(CXXFLAGS) $< -o $@
	$(STRIP) $@

clean:
	-rm -f $(TARGETS) $(SUIDBUILDS)

.PHONY: install-setuid $(SUIDTARGETS)
