#!/usr/bin/env python

from os     import popen3
from sys    import argv, stdout, stderr, exit
from thread import start_new_thread
from time   import sleep


class PlayerException(Exception):
    def __init__(self, value, player):
        self.value  = value
        self.player = player

    def __str__(self):
        return '%s caused exception: %s.' % (self.player, self.description())


class NotResponding(PlayerException):
    "Pipe to player process died or timed out"

    def description(self):
        return 'process not responding; %s' % self.value


class InvalidSyntax(PlayerException):
    "Syntax error in player communication"

    def description(self):
        return 'invalid syntax; %s' % self.value


class InvalidMove(PlayerException):
    "Player made an invalid move"

    def description(self):
        return 'invalid move; %s' % self.value



def run_error_thread(name, error):
    while True:
        line = error.readline()
        if line:
            print '%s> %s' % (name, line.rstrip())
        else:
            break


class Player:
    """Represents a player in the game and provides methods for communicating
       with the player."""

    def __init__(self, name, input, output, error):
        self.name   = name
        self.input  = input
        self.output = output
        start_new_thread(run_error_thread, (name, error))

    def __str__(self):
        return self.name

    def read_line(self):
        line = self.input.readline()
        if line.endswith('\n'):
            return line.rstrip('\n')
        else:
            raise NotResponding('unexpected end of input', self)

    def write(self, data):
        try:
            self.output.write(data)
            self.output.flush()
        except IOError, e:
            raise NotResponding(e, self)

    def write_line(self, data):
        #print 'Sending "%s" to %s' % (data, self.name)
        self.write(str(data) + "\n")

    def quit(self):
        try:
            self.write('Quit\n')
            self.close()
        except IOError, e:
            pass

    def close(self):
        try:
            self.output.close()
            self.read_line()
        except NotResponding, e:
            pass


def parse_int(self, str, min, max):
    try:
        i = int(str)
    except ValueError, e:
        raise InvalidSyntax('integer expected', self)
    if i < min or i > max:
        raise InvalidMove('integer between %d and %d (inclusive) expected' % (min, max), self)
    return i



class World:
    "Represents a game world"

    def __init__(self, filepath):
        f = file(filepath)
        self.nodes = int(f.readline())
        self.edges = []
        while True:
            line = f.readline().rstrip()
            if line == 'END':
                break
            a, b = map(int, line[2:].split('-'))
            self.edges.append((a, b, line[0]))
            self.edges.append((b, a, line[0]))


class Game:

    def __init__(self, world, fugitive, detectives):
        self.world      = world
        self.fugitive   = fugitive
        self.detectives = detectives

    def run(self):
        moves      = []
        result     = '20-0'
        resultDesc = 'The fugitive escaped!'
        sendquit   = True

        try:
            self.fugitive.write_line("Fugitive")
            self.detectives.write_line("Detectives")

            # Set-up
            cops = []
            for _ in range(4):
                pos = parse_int(self.detectives, self.detectives.read_line(), 1, self.world.nodes)
                if pos in cops:
                    raise InvalidMove('two detectives in town %d' % pos, self.detectives)
                cops.append(pos)
                moves.append(pos)
                self.fugitive.write_line(pos)
            crook = parse_int(self.fugitive, self.fugitive.read_line(), 1, self.world.nodes)
            self.detectives.write_line(crook)
            moves.append(crook)

            for move in range(1, 51):

                # Read fugitive's move
                line = self.fugitive.read_line()
                if len(line) < 3 or line[0] not in 'CTP' or line[1] <> ' ':
                    raise InvalidSyntax(line, self.fugitive)
                mode = line[0]
                try:
                    dest = parse_int(self.fugitive, line[2:], 1, self.world.nodes)
                except ValueError, e:
                    raise InvalidSyntax(line, self.fugitive)
                if (crook, dest, mode) not in self.world.edges:
                    raise InvalidMove(line, self.fugitive)
                crook = dest
                moves.append('%c:%d' % (mode, dest))

                if crook in cops:
                    result = '%d-%d' % ((move-1)/5, 20 - (move-1)/5)
                    resultDesc = 'The fugitive ran into a detective in town %d!' % crook
                    break

                if move%5:
                    self.detectives.write_line(mode)
                else:
                    self.detectives.write_line('%c %d' % (mode, dest))

                # Read detectives' moves
                for c in range(4):
                    dest = parse_int(self.detectives, self.detectives.read_line(), 1, self.world.nodes)
                    if (cops[c], dest, 'C') not in self.world.edges and \
                       (cops[c], dest, 'T') not in self.world.edges and \
                       (cops[c], dest, 'P') not in self.world.edges:
                        raise InvalidMove(line, self.detectives)
                    if dest in cops[:c]:
                        raise InvalidMove('two detectives in town %d' % pos, self.detectives)
                    cops[c] = dest
                    moves.append(dest)
                    if move < 50:
                        self.fugitive.write_line(dest)

                if crook in cops:
                    result = '%d-%d' % (move/5, 20 - move/5)
                    resultDesc = 'Detective %d caught the fugitive in town %d!' % (cops.index(crook) + 1, crook)
                    break

                if move == 50:
                    sendquit = False

        except PlayerException, e:
            print >> stderr, e

            if e.player == self.fugitive:
                resultDesc = 'The fugitive is disqualified!'
                result = '0-20'

            if e.player == self.detectives:
                resultDesc = 'The detectives are disqualified!'
                result = '20-0'

        try:
            if sendquit:
                self.detectives.quit()
                self.fugitive.quit()
            else:
                self.detectives.close()
                self.fugitive.close()
        except:
            pass


        stderr.flush()
        print >>stdout, "moves="+(','.join(map(str, moves)))
        print >>stdout, "result="+result
        print >>stdout, "resultDesc="+resultDesc
        stdout.flush()


if __name__ == '__main__':
    if len(argv) <> 4:
        print 'Usage: %s <worldfile> <command1> <command2>' % argv[0]
        exit()

    world = World(argv[1])
    input, output, error = popen3(argv[2])
    fugitive = Player('Fugitive', output, input, error)
    input, output, error = popen3(argv[3])
    detectives = Player('Detectives', output, input, error)
    game = Game(world, fugitive, detectives)
    game.run()

    # Allow clients to send final output
    sleep(1)
    
