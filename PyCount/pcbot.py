# -----------------------------------------------------------------------------------------------
# Library imports.
import re, time, datetime, MySQLdb
from contextlib import closing

# -----------------------------------------------------------------------------------------------
# Global variables.

# Create the main connection to the database
g_Db = MySQLdb.connect('localhost','root','mysql','mybb')
# Whether the main loop should continue
g_RunLoop = True
# The number of posts that a user must make in a month
g_PostCount = 15
# The identifier of the user that is used to represent the bot
# - Must be different from manager id because it'd be weird for the manager to send PMs to himself
g_BotUserID = 2
# The identifiers of the users that can perform administrative commands on this bot
g_ManagerID = [1]
# The identifier of the group of users that need regular post counts
g_OwnerGroup = 2
# The identifier of the group of users that need double the post count
g_MultiGroup = 8
# The list of users that can request for counts
g_OwnerList = None
# The list of users where the post count is double
g_MultiList = None
# Identifiers of forums/categories to exclude from count
g_ForumFilter = [
    21, # SPAM/Testing
    25  # Introductions
]
# Keep track of the identifier of the last processed shout message
g_LastShoutID = None
# A URL to the root of the forum without the trailing slash
g_SiteURL = 'https://freevps.us'
# A URL to use when generating links to users
g_UserURL = 'https://freevps.us/user-{uid}.html'
# A URL to use when generating links to posts
g_PostURL = 'https://freevps.us//thread-{tid}-post-{pid}.html#pid{pid}'

# -----------------------------------------------------------------------------------------------
# Pre-assembled information.

# Pre-compiled string of forum filters for queries
g_IgnoredForums = "','".join(str(n) for n in g_ForumFilter)

# -----------------------------------------------------------------------------------------------
# Update the last shout identifier.
def UpdateLastShoutID():
    # Import globals
    global g_LastShoutID, g_Db
    # Obtain a database cursor and proceed to query the database
    with closing(g_Db.cursor()) as cursor:
        # Prevent the cursor from giving us cached results
        g_Db.begin()
        # Select the identifier of the last inserted shout-box message
        try:
            cursor.execute("SELECT id FROM mybb_dvz_shoutbox ORDER BY id DESC LIMIT 1")
        except Exception as e:
            # Display information
            print('Failed to update last shout id: {:s}'.format(str(e)))
            # Specify that we failed to deliver
            return False
        # We selected one row so let's try and get just that
        row = cursor.fetchone()
        # Was there a shout available or should we start from zero?
        g_LastShoutID = 0 if row == None else int(row[0])
    # At this point everything should have worked
    return True

# -----------------------------------------------------------------------------------------------
# Initialize the last shout identifier
UpdateLastShoutID()

# -----------------------------------------------------------------------------------------------
# Update the owners list.
def UpdateOwnersList():
    # Import globals
    global g_OwnerGroup, g_MultiGroup, g_OwnerList, g_MultiList, g_Db
    # Clear the current user list now so that in case something goes bad, no one can query anything
    g_OwnerList, g_MultiList = [], []
    # Obtain a database cursor and proceed to query the database
    with closing(g_Db.cursor()) as cursor:
        # Select the identifiers of users that are allowed to query for post counts
        try:
            cursor.execute("SELECT uid, additionalgroups FROM mybb_users WHERE usergroup = {0} OR FIND_IN_SET('{0}', additionalgroups)".format(g_OwnerGroup))
        except Exception as e:
            # Display information
            print('Failed to update owners list: {:s}'.format(str(e)))
            # Specify that we failed to deliver
            return False
        # Was there any user in this group?
        if not cursor.rowcount:
            return True
        # Iterate over the retrieved rows and update the user list
        for row in cursor:
            # Add the user to the list with regular post counts
            g_OwnerList.append(int(row[0]))
            # See if this user appears in the dual-count as well
            if str(g_MultiGroup) in row[1].split(','):
                # Add it to the list with dual post counts
                g_MultiList.append(int(row[0]))
    # At this point everything should have worked
    return True

# -----------------------------------------------------------------------------------------------
# Populate the user list for the first time
UpdateOwnersList()

# -----------------------------------------------------------------------------------------------
# Updates a users private message count in the users table with the number of PMs they have.
def UpdateUserPMs(userid):
    # Import globals
    global g_Db
    # Obtain a database cursor and proceed to query the database
    with closing(g_Db.cursor()) as cursor:
        # Prevent the cursor from giving us cached results
        g_Db.begin()
        # Select a count the total number of PMs
        try:
            cursor.execute("SELECT pmid FROM mybb_privatemessages WHERE uid = {:d}".format(userid))
        except Exception as e:
            # Display information
            print('Failed to select total PM count: {:s}'.format(str(e)))
            # Specify that we failed to deliver
            return False
        # Grab the result of the count
        total = int(cursor.rowcount)
        # Select a count the total number of unread PMs
        try:
            cursor.execute("SELECT pmid FROM mybb_privatemessages WHERE uid = {:d} AND status = 0 AND folder = 1".format(userid))
        except Exception as e:
            # Display information
            print('Failed to select unread PM count: {:s}'.format(str(e)))
            # Specify that we failed to deliver
            return False
        # Grab the result of the count
        unread = int(cursor.rowcount)
        # Update the number of total and unread PMs
        try:
            cursor.execute("UPDATE mybb_users SET totalpms = {:d}, unreadpms = {:d} WHERE uid = {:d}".format(total, unread, userid))
            # Attempt to commit changes to database
            g_Db.commit()
        except Exception as e:
            # Display information
            print('Failed to update PM count: {:s}'.format(str(e)))
            # Roll back changes
            g_Db.rollback()
            # Specify that we failed to deliver
            return False
    # At this point everything should have worked
    return True

# -----------------------------------------------------------------------------------------------
# Send a private message to a certain user.
def SendPrivateMessage(sender, receiver, subject, message):
    # Import globals
    global g_Db
    # Obtain a database cursor and proceed to query the database
    with closing(g_Db.cursor()) as cursor:
        # Prevent the cursor from giving us cached results
        g_Db.begin()
        # I have no idea how this is formed, I just know it's supposed to be like this if only one user receives this message
        recipients = MySQLdb.escape_string('a:1:{s:2:"to";a:1:{i:0;s:1:"1";}}')
        # Escape received information
        subject = MySQLdb.escape_string(subject)
        message = MySQLdb.escape_string(message)
        # Correct value types, if necessary
        sender = int(sender)
        receiver = int(receiver)
        # Inject our PM into the MyBB database
        try:
            cursor.execute("INSERT INTO mybb_privatemessages (uid, toid, fromid, recipients, subject, message, dateline, ipaddress) VALUES ({1}, {1}, {0}, '{2}', '{3}', '{4}', {5}, UNHEX('00000000000000000000000000000001'))".format(sender, receiver, recipients, subject, message, int(time.time())))
            # Specify that this user should receive a notice about new PMs
            cursor.execute("UPDATE mybb_users SET pmnotice = 2 WHERE uid = {:d}".format(receiver));
            # Attempt to commit changes to database
            g_Db.commit()
        except Exception as e:
            # Display information
            print('Failed to send PM: {:s}'.format(str(e)))
            # Roll back changes
            g_Db.rollback()
            # Specify that we failed to deliver
            return False
    # We should update the PM count of this user
    UpdateUserPMs(receiver)
    # At this point everything should have worked
    return True


# -----------------------------------------------------------------------------------------------
# Actual implementation of post counting function.
def CountUserPostsImpl(userid, query):
    # Import globals
    global g_Db
    # Obtain a database cursor and proceed to query the database
    with closing(g_Db.cursor()) as cursor:
        # Prevent the cursor from giving us cached results
        g_Db.begin()
        # Select the identifiers of the posts that match our criteria
        try:
            cursor.execute(query)
        except Exception as e:
            # Display information
            print('Failed to count posts: {:s}'.format(str(e)))
            # Default to zero posts
            return 0
        # Return the number of rows as the result of the count
        return int(cursor.rowcount)
    # If we somehow reached this point, default to zero
    return 0

# -----------------------------------------------------------------------------------------------
# Get the number of posts after a certain date-time.
def CountUserPostsAfter(userid, dtm):
    # Import globals
    global g_IgnoredForums
    # Grab the time-stamp from the specified date-time
    dateline = int(time.mktime(dtm.timetuple()))
    # Generate the query string with the specified information
    query = "SELECT pid FROM mybb_posts WHERE uid = {:d} AND dateline > {:d} AND fid NOT IN ('{:s}')".format(userid, dateline, g_IgnoredForums)
    # Forward the call to the actual implementation and return the result
    return CountUserPostsImpl(userid, query)

# -----------------------------------------------------------------------------------------------
# Get the number of posts between two date-times.
def CountUserPostsBetween(userid, bdt, edt):
    # Import globals
    global g_IgnoredForums
    # Grab the time-stamps from the specified date-times
    begin = int(time.mktime(bdt.timetuple()))
    end = int(time.mktime(edt.timetuple()))
    # Generate the query string with the specified information
    query = "SELECT pid FROM mybb_posts WHERE uid = {:d} AND dateline BETWEEN {:d} AND {:d} AND fid NOT IN ('{:s}')".format(userid, begin, end, g_IgnoredForums)
    # Forward the call to the actual implementation and return the result
    return CountUserPostsImpl(userid, query)

# -----------------------------------------------------------------------------------------------
# Actual implementation of post reporting function.
def ReportUserPostsImpl(userid, query, dtsub, dtmsg):
    # Import globals
    global g_Db, g_BotUserID, g_MultiList, g_PostCount, g_PostURL
    # Obtain a database cursor and proceed to query the database
    with closing(g_Db.cursor()) as cursor:
        # Prevent the cursor from giving us cached results
        g_Db.begin()
        # Select brief information of the posts that match our criteria
        try:
            cursor.execute(query)
        except Exception as e:
            # Display information
            print('Failed to report posts: {:s}'.format(str(e)))
            # Specify that we failed to deliver
            return False
        # Compute the number of posts that the user needs
        need = g_PostCount * 2 if userid in g_MultiList else g_PostCount
        # Was there any post that matched our criteria?
        if not cursor.rowcount:
            # Send this user a PM explaining that he has no posts in this period of time
            return SendPrivateMessage(g_BotUserID, userid, '0 posts made {:s}'.format(dtsub), 'You do not have any posts made {:s}. Your monthly posts requirement is [b]{:d}[/b] posts.'.format(dtmsg, need))
        # Grab the post count
        count = int(cursor.rowcount)
        # Generate the message header
        msg = 'You have [b]{:d}[/b] post(s) made {:s}. Your monthly posts requirement is [b]{:d}[/b] posts.\n[hr]\nHere is a list of the posts included in this count:\n[list]\n'.format(count, dtmsg, need)
        # Build a list of posts that were included in this count
        for row in cursor:
            #Generate the post URL
            url = g_PostURL.format(tid=int(row[1]), pid=int(row[0]))
            # Generate the element list and append it
            msg += '[*][url={0}]{1}[/url]\n'.format(url, row[2])
        # Close the list
        msg += '\n[/list]'
        # Finally, send the PM to the user and return the result
        return SendPrivateMessage(g_BotUserID, userid, '{:d} post(s) made {:s}'.format(count, dtsub), msg)
    # At this point everything should have worked
    return True

# -----------------------------------------------------------------------------------------------
# Report the number of posts after a certain date-time.
def ReportUserPostsAfter(userid, dtm):
    # Import globals
    global g_IgnoredForums
    # Grab the time-stamp from the specified date-time
    dateline = int(time.mktime(dtm.timetuple()))
    # Generate the query string with the specified information
    query = "SELECT pid, tid, subject FROM mybb_posts WHERE uid = {:d} AND dateline > {:d} AND fid NOT IN ('{:s}')".format(userid, dateline, g_IgnoredForums)
    # Forward the call to the actual implementation and return the result
    return ReportUserPostsImpl(userid, query, 'after {:s}'.format(str(dtm)), 'after [b]{:s}[/b]'.format(str(dtm)))

# -----------------------------------------------------------------------------------------------
# Report the number of posts after a certain date-time.
def ReportUserPostsBetween(userid, bdt, edt):
    # Import globals
    global g_IgnoredForums
    # Grab the time-stamp from the specified date-time
    begin = int(time.mktime(bdt.timetuple()))
    end = int(time.mktime(edt.timetuple()))
    # Generate the query string with the specified information
    query = "SELECT pid, tid, subject FROM mybb_posts WHERE uid = {:d} AND dateline BETWEEN {:d} AND {:d} AND fid NOT IN ('{:s}')".format(userid, begin, end, g_IgnoredForums)
    # Forward the call to the actual implementation and return the result
    return ReportUserPostsImpl(userid, query, 'between {:s} and {:s}'.format(str(bdt), str(edt)), 'between [b]{:s}[/b] and [b]{:s}[/b]'.format(str(bdt), str(edt)))

# -----------------------------------------------------------------------------------------------
# Retrieve the name of the specified user identifier.
def FetchUserName(userid):
    # Import globals
    global g_Db
    # Obtain a database cursor and proceed to query the database
    with closing(g_Db.cursor()) as cursor:
        # Select the name of the specified user
        try:
            cursor.execute("SELECT username FROM mybb_users WHERE uid = {:d} LIMIT 1".format(userid))
        except Exception as e:
            # Display information
            print('Failed to acquire user name: {:s}'.format(str(e)))
            # Specify that we failed to deliver
            return None
        # We selected one row so let's try and get just that
        row = cursor.fetchone()
        # Was there a user with that identifier?
        return None if row == None else str(row[0])

# -----------------------------------------------------------------------------------------------
# Generate the report to send to the administrator who requested the alert/warn
def GenerateAdminReport(adminid, status, warn, bdt, edt):
    # Import globals
    global g_UserURL, g_BotUserID
    # The type of report
    rtype = 'warning' if warn == True else 'alert'
    # Open the list of users which completed their post count, didn't or failed to receive the notice
    clist = '\nThe list of users who completed their posts:\n[list]\n'
    flist = '\nThe list of users who failed to complete their posts:\n[list]\n'
    elist = '\nThe list of users who the counter failed to send the notice:\n[list]\n'
    # The number of users which completed their post count, didn't or failed to receive the notice
    cnum, fnum, enum = 0, 0, 0
    # Start generating the lists
    for us in status:
        # The URL to this user profile
        url = g_UserURL.format(uid=us['userid']);
        # The name of the user
        name =  FetchUserName(us['userid'])
        # Did we fail to send a notice to this user?
        if us['error'] == True:
            # Add it to the list of users which the counter failed to send the notice
            elist += '[*][url={0}]{1}[/url]: [b]{2}[/b]\n'.format(url, name, us['reason'])
            # Increment the users which the counter failed to send the notice
            enum += 1
        # Did this user completed his posts?
        elif us['made'] >= us['need']:
            # Add it to the list of users which completed their posts
            clist += '[*][url={0}]{1}[/url]: [b]{2} / {3}[/b]\n'.format(url, name, us['made'], us['need'])
            # Increment the users which completed their posts
            cnum += 1
        else:
            # Add it to the list of users which didn't complete their posts
            flist += '[*][url={0}]{1}[/url]: [b]{2} / {3}[/b]\n'.format(url, name, us['made'], us['need'])
            # Increment the users which didn't complete their posts
            fnum += 1
    # Was there any user who completed their posts?
    if cnum <= 0:
        clist += '[*]No user completed their posts.\n'
    # Was there any user who didn't complete their posts?
    if fnum <= 0:
        flist += '[*]No user failed to complete their posts.\n'
    # Was there any user which the counter failed to send the notice?
    if enum <= 0:
        elist += '[*]All users received their post notice.\n'
    # Close the post count lists
    clist += '[/list]\n'
    flist += '[/list]\n'
    elist += '[/list]\n'
    # Generate the report message to send to the administrator
    msg = 'Here is the report of the post {0} between ([b]{1}[/b]) and ([b]{2}[/b]).\n[hr]{3}[hr]{4}[hr]{5}'.format(rtype, str(bdt), str(edt), clist, flist, elist)
    # Finally, send the message to the administrator
    return SendPrivateMessage(g_BotUserID, adminid, 'Report of post {0} between {1} and {2}'.format(rtype, str(bdt), str(edt)), msg)

# -----------------------------------------------------------------------------------------------
# Generate the message to be sent as a alert.
def GenerateAlertMessage(admin, bdate, edate, ndate, made, need, cursor):
    # Import globals
    global g_PostURL
    # Start with an empty list of posts
    plist = ""
    # Did the user made any posts?
    if made > 0:
        # Open the post list
        plist += '\n[color=#333333][size=small][font=Arial]The posts that were included in this count are:\n[list]\n'
        # Build a list of posts that were included in this count
        for row in cursor:
            #Generate the post URL
            url = g_PostURL.format(tid=int(row[1]), pid=int(row[0]))
            # Generate the element list and append it
            plist += '[*][url={0}]{1}[/url]\n'.format(url, row[2])
        # Close the list
        plist += '\n[/list]\n[/font][/size][/color]\n'
    # Generate the message and return it
    return """[font=Arial][color=#333333][size=small]Dear respected FreeVPS Directory & Discussion Forum Member & VPS Owner!
[/size][/color][/font]

[color=#333333][size=small][font=Arial]I am contacting you because you've missing posts that have to be made up between ([b]{bdate}[/b]) and ([b]{edate}[/b]) to keep your VPS for the month ([b]{nextm} {nexty}[/b])
[/font][/size][/color]

[color=#333333][size=small][font=Arial]Amount: [b]{posts}[/b] Post(s). Required: [b]{required}[/b] Post(s).
[/font][/size][/color]
{postlist}
[color=#333333][size=small][font=Arial]If you believe you have received this message in error, eg, you have already made up your posts, or the posts were counted incorrectly please let me know before ([b]{edate}[/b]) and I will double check your posts.[/font][/size][/color]

[color=#333333][size=small][font=Arial]You are being given time until ([b]{edate}[/b]) to make up all your missing posts and [b]reply to this message to confirm[/b] that you've made up all the missing posts.[/font][/size][/color]

[color=#333333][size=small][font=Arial][b]The last moment to message me back is ({edate}). Any messages received after that may not be accepted![/b][/font][/size][/color]
[color=#333333][size=small][font=Arial][b]VPSs of members who have [color=red]failed to make up their missing posts, will be terminated before the next giveaway[/color] and will be made available to other members during the next giveaway.[/b]
[/font][/size][/color]

[color=#333333][size=small][font=Arial]Note that posts in the "SPAM/Testing" and "Introductions" forums DO NOT count towards your post count as we've disabled the post count there a long time ago.[/font][/size][/color]
[color=#333333][size=small][font=Arial]Only real and valid excuses with proper proof will be accepted. Do not use this to get out of making posts unless you have a genuine and applicable reason for doing so.
[/font][/size][/color]

[color=#333333][size=small][font=Arial]Yours sincerely,[/font][/size][/color]
[color=#333333][size=small][font=Arial][b]{manager}[/b][/font][/size][/color]
[font=Arial][color=#333333][size=small]Giveaway Manager[/size][/color][/font]
[font=Arial][color=#333333][size=small]FreeVPS Directory & Discussion Staff & Administration[/size][/color][/font]""".format(
        bdate=bdate.strftime("%d/%B/%Y %H:%M:%S"),
        edate=edate.strftime("%d/%B/%Y %H:%M:%S"),
        ndate=bdate.strftime("%d/%B/%Y %H:%M:%S"),
        nextm=ndate.strftime("%B"),
        nexty=ndate.year,
        posts=made,
        required=need,
        manager=admin,
        postlist=plist
    )

# -----------------------------------------------------------------------------------------------
# Generate the message to be sent as an warning.
def GenerateWarningMessage(admin, bdate, edate, ndate, made, need, cursor):
    # Import globals
    global g_PostURL
    # Start with an empty list of posts
    plist = ""
    # Did the user made any posts?
    if made > 0:
        # Open the post list
        plist += '\n[color=#333333][size=small][font=Arial]The posts that were included in this count are:\n[list]\n'
        # Build a list of posts that were included in this count
        for row in cursor:
            # Generate the post URL
            url = g_PostURL.format(tid=int(row[1]), pid=int(row[0]))
            # Generate the element list and append it
            plist += '[*][url={0}]{1}[/url]\n'.format(url, row[2])
        # Close the list
        plist += '\n[/list]\n[/font][/size][/color]\n'
    # Generate the message and return it
    return """[font=Arial][color=#333333][size=small]Dear respected FreeVPS Directory & Discussion Forum Member & VPS Owner!
[/size][/color][/font]

[color=#333333][size=small][font=Arial]I am contacting you because you've failed to complete posts that had to be made between ([b]{bdate}[/b]) and ([b]{edate}[/b]) to keep your VPS for the month ([b]{nextm} {nexty}[/b])
[/font][/size][/color]

[color=#333333][size=small][font=Arial]Amount: [b]{posts}[/b] Post(s). Required: [b]{required}[/b] Post(s).
[/font][/size][/color]
{postlist}
[color=#333333][size=small][font=Arial]If you believe you have received this message in error, eg, you have already made up your posts, or the posts were counted incorrectly please let me know as soon as possible and I will double check your posts.[/font][/size][/color]

[color=#333333][size=small][font=Arial]This was the final warning to complete your posts. Meaning that [b]your VPS will soon be terminated[/b]. If you have anything of importance stored or associated with the VPS we highly recommend you to backup your data and/or take the necessary precautions.[/font][/size][/color]

[color=#333333][size=small][font=Arial]Note that posts in the "SPAM/Testing" and "Introductions" forums DO NOT count towards your post count as we've disabled the post count there a long time ago.[/font][/size][/color]
[color=#333333][size=small][font=Arial]Only real and valid excuses with proper proof will be accepted. Do not use this to get out of making posts unless you have a genuine and applicable reason for doing so.
[/font][/size][/color]

[color=#333333][size=small][font=Arial]Yours sincerely,[/font][/size][/color]
[color=#333333][size=small][font=Arial][b]{manager}[/b][/font][/size][/color]
[font=Arial][color=#333333][size=small]Giveaway Manager[/size][/color][/font]
[font=Arial][color=#333333][size=small]FreeVPS Directory & Discussion Staff & Administration[/size][/color][/font]""".format(
        bdate=bdate.strftime("%d/%B/%Y %H:%M:%S"),
        edate=edate.strftime("%d/%B/%Y %H:%M:%S"),
        ndate=bdate.strftime("%d/%B/%Y %H:%M:%S"),
        nextm=ndate.strftime("%B"),
        nexty=ndate.year,
        posts=made,
        required=need,
        manager=admin,
        postlist=plist
    )

# -----------------------------------------------------------------------------------------------
# Run a complete count and generate an administrator report.
def AdminReport(adminid, bdt, edt, silent, warn):
    # Import globals
    global g_Db, g_PostCount, g_OwnerList, g_MultiList, g_IgnoredForums
    # Add one day to end date for the next month
    ndt = edt + datetime.timedelta(days=1)
    # Now reset to the beginning of the month
    ndt.replace(day=1, hour=0, minute=0, second=0, microsecond=0)
    # Grab the time-stamp from the specified date-time
    begin = int(time.mktime(bdt.timetuple()))
    end = int(time.mktime(edt.timetuple()))
    # Attempt to retrieve the name of the administrator
    admin = FetchUserName(adminid)
    # Make sure the owner list is up to date
    if UpdateOwnersList() != True:
        # Specify that we failed to deliver
        return False
    # The list of processed users
    status = []
    # Start processing owner identifiers
    for userid in g_OwnerList:
        # Obtain a database cursor and proceed to query the database
        with closing(g_Db.cursor()) as cursor:
            # Select the post count for the specified date range
            try:
                cursor.execute("SELECT pid, tid, subject FROM mybb_posts WHERE uid = {:d} AND dateline BETWEEN {:d} AND {:d} AND fid NOT IN ('{:s}')".format(userid, begin, end, g_IgnoredForums))
            except Exception as e:
                # Display information
                print('Failed generate staff report: {:s}'.format(str(e)))
                # Specify that we failed to send a notice to this user
                status.append({'error': True, 'userid': userid, 'reason': str(e)})
                # Proceed to the next user
                continue
            # The number of posts that are included in this count
            made = 0 if not cursor.rowcount else int(cursor.rowcount)
            # The number of posts that the user must complete
            need = g_PostCount * 2 if userid in g_MultiList else g_PostCount
            # Did the user complete his posts? And Are we supposed to send a report?
            if made >= need or silent == True:
                # Specify that it was not required to send a notice to this user
                status.append({'error': False, 'userid': userid, 'made': made, 'need': need, 'reason': 'Unknown'})
                # We don't need to send a notice to this user
                continue
            # Generate the user report
            msg = GenerateWarningMessage(admin, bdt, edt, ndt, made, need, cursor) if warn == True else GenerateAlertMessage(admin, bdt, edt, ndt, made, need, cursor)
            # Finally, send the PM to the user and move on to the next user
            result = SendPrivateMessage(adminid, userid, '[{0}] Missing {1} post(s) between {2} and {3}'.format('WARNING' if warn == True else 'ALERT', need - made, bdt, edt), msg)
            # Store the status of this user
            status.append({'error': not result, 'userid': userid, 'made': made, 'need': need, 'reason': 'Failed to send PM'})
    # Finally, generate the administrator report and return the result
    return GenerateAdminReport(adminid, status, warn, bdt, edt)

# -----------------------------------------------------------------------------------------------
# Process submitted commands.
def ProgramPulse():
    # Import globals
    global g_LastShoutID, g_Db, g_RunLoop
    # Obtain a database cursor and proceed to query the database
    with closing(g_Db.cursor()) as cursor:
        # Prevent the cursor from giving us cached results
        g_Db.begin()
        # Select any shout messages that might have been posted while we were busy
        cursor.execute("SELECT id, uid, text FROM mybb_dvz_shoutbox WHERE id > {:d}".format(g_LastShoutID))
        # Was there any new shout message?
        if not cursor.rowcount:
            # Take a break
            try:
                time.sleep(1)
            except:
                # Stop the loop
                g_RunLoop = False
                # I know. nasty. so what?
                pass
            # Try again
            return
        # Iterate over the returned rows
        for row in cursor:
            # Grab the shout identifier
            shoutid, userid = int(row[0]), int(row[1])
            # Exclude it on the next loop
            if shoutid > g_LastShoutID:
                g_LastShoutID = shoutid
            # Is this a command?
            try:
                if row[2].index('#') != 0:
                    # Just raise and exception and continue the loop when catching it
                    raise Exception('Not a command!')
            except:
                continue
            # Start with an empty command
            cmd_list = None
            # Attempt to extract a clean command
            try:
                cmd_list = re.sub(r'[^0-9a-z.]', '', row[2].lower()).split('.')
            except:
                continue
            # Import globals
            global g_ManagerID, g_OwnerList
            # Grab the number of command sections
            sections = len(cmd_list)
            # Is there a command to process?
            if sections < 1 or cmd_list[0] != 'pcbot':
                continue
            # Is this user allowed to send commands?
            elif userid not in g_OwnerList and userid not in g_ManagerID:
                # Update the shout message to display the warning
                cursor.execute("UPDATE mybb_dvz_shoutbox SET text = 'You do not have the privilege to send commands', modified = {:d} WHERE id = {:d}".format(int(time.time()), shoutid))
                # Skip the command
                continue
            # Is this a valid command?
            elif sections < 2:
                # Update the shout message to display the warning
                cursor.execute("UPDATE mybb_dvz_shoutbox SET text = 'Incomplete Command: {:s}', modified = {:d} WHERE id = {:d}".format('.'.join(cmd_list), int(time.time()), shoutid))
                # Skip the command
                continue
            # Grab the main command
            cmd = cmd_list[1]
            # Command: a user requesting a count
            if cmd == 'count':
                # Grab the sub command, if any, or default to 'current'
                scmd = cmd_list[2] if sections > 2 else 'current'
                # The reply message to replace the command shout
                reply = None
                # Command: count posts from current month
                if scmd == 'current':
                    # Grab the current time and move to the beginning of the month
                    dtm = datetime.datetime.now().replace(day=1,hour=0,minute=0,second=0,microsecond=0)
                    # Attempt to count the user posts
                    pcount = CountUserPostsAfter(userid, dtm)
                    # Now generate the reply message
                    reply = '{:d} posts made after ({:s})'.format(pcount, str(dtm))
                # Command: count posts from previous month
                elif scmd == 'previous':
                    # Subtract 1 second from the beginning of the current month so we jump to the end of previous month
                    edt = datetime.datetime.now().replace(day=1,hour=0,minute=0,second=0,microsecond=0) - datetime.timedelta(seconds=1)
                    # Now use that to obtain the beginning of the previous month as well
                    bdt = edt.replace(day=1,hour=0,minute=0,second=0)
                    # Attempt to count the user posts
                    pcount = CountUserPostsBetween(userid, bdt, edt)
                    # Now generate the reply message
                    reply = '{:d} posts made between ({:s}) and ({:s})'.format(pcount, str(bdt), str(edt))
                # Command: count posts from today
                elif scmd == 'today':
                    # Grab the current time and move to the beginning of the day
                    bdt = datetime.datetime.now().replace(hour=0,minute=0,second=0,microsecond=0)
                    # Grab the current time and move to the end of the day
                    edt = datetime.datetime.now().replace(hour=23,minute=59,second=59,microsecond=0)
                    # Attempt to count the user posts
                    pcount = CountUserPostsBetween(userid, bdt, edt)
                    # Now generate the reply message
                    reply = '{:d} posts made between ({:s}) and ({:s})'.format(pcount, str(bdt), str(edt))
                # Command: count posts from yesterday
                elif scmd == 'yesterday':
                    # Grab the current time and move to the beginning of the day
                    dtm = datetime.datetime.now().replace(hour=0,minute=0,second=0,microsecond=0)
                    # Subtract one second to obtain the end of yesterday
                    edt = dtm - datetime.timedelta(seconds=1)
                    # Subtract one day to obtain the beginning of yesterday
                    bdt = dtm - datetime.timedelta(days=1)
                    # Attempt to count the user posts
                    pcount = CountUserPostsBetween(userid, bdt, edt)
                    # Now generate the reply message
                    reply = '{:d} posts made between ({:s}) and ({:s})'.format(pcount, str(bdt), str(edt))
                # Command: count posts from a certain amount of hours
                elif scmd == 'hour':
                    # The default number of hours to go back
                    hsub = 1
                    # Was there a specific number of hours to go back?
                    if sections > 3:
                        try:
                            hsub = max(min(23, int(cmd_list[3])), 1)
                        except:
                            pass
                    # Grab the current time
                    edt = datetime.datetime.now().replace(microsecond=0)
                    # Subtract the specified hours from the current time
                    bdt = edt - datetime.timedelta(hours=int(hsub))
                    # Attempt to count the user posts
                    pcount = CountUserPostsBetween(userid, bdt, edt)
                    # Now generate the reply message
                    reply = '{:d} posts made between ({:s}) and ({:s})'.format(pcount, str(bdt), str(edt))
                else:
                    # Generate the reply message to warn the user
                    reply = 'Unknown Range: {:s}'.format(scmd)
                # Now that we're here, let's execute the resulted query
                try:
                    cursor.execute("UPDATE mybb_dvz_shoutbox SET text = '{:s}', modified = {:d} WHERE id = {:d}".format(reply, int(time.time()), shoutid))
                except Exception as e:
                    # Display information
                    print('Failed to generate count: {:s}'.format(str(e)))
                    # Ignore the error
                    pass
                # Move to the next command
                continue
            # Command: report the post count via PM
            elif cmd == 'report':
                # Grab the sub command, if any, or default to 'current'
                scmd = cmd_list[2] if sections > 2 else 'current'
                # The success and failure messages to keep code shorter
                success, failure = 'Please check your private messages', 'However, an error occurred. Please get in touch with a staff member'
                # The reply message to replace the command shout
                reply = None
                # Command: report posts from current month
                if scmd == 'current':
                    # Grab the current time and move to the beginning of the month
                    dtm = datetime.datetime.now().replace(day=1,hour=0,minute=0,second=0,microsecond=0)
                    # Attempt to generate a report of the user posts
                    result = ReportUserPostsAfter(userid, dtm)
                    # Now generate the reply message
                    reply = 'Report of posts after ({:s}) was queued. {:s}'.format(str(dtm), success if result else failure)
                # Command: report posts from previous month
                elif scmd == 'previous':
                    # Subtract 1 second from the beginning of the current month so we jump to the end of previous month
                    edt = datetime.datetime.now().replace(day=1,hour=0,minute=0,second=0,microsecond=0) - datetime.timedelta(seconds=1)
                    # Now use that to obtain the beginning of the previous month as well
                    bdt = edt.replace(day=1,hour=0,minute=0,second=0)
                    # Attempt to generate a report of the user posts
                    result = ReportUserPostsBetween(userid, bdt, edt)
                    # Now generate the reply message
                    reply = 'Report of posts between ({:s}) and ({:s}) was queued. {:s}'.format(str(bdt), str(edt), success if result else failure)
                # Command: report posts from today
                elif scmd == 'today':
                    # Grab the current time and move to the beginning of the day
                    bdt = datetime.datetime.now().replace(hour=0,minute=0,second=0,microsecond=0)
                    # Grab the current time and move to the end of the day
                    edt = datetime.datetime.now().replace(hour=23,minute=59,second=59,microsecond=0)
                    # Attempt to generate a report of the user posts
                    result = ReportUserPostsBetween(userid, bdt, edt)
                    # Now generate the reply message
                    reply = 'Report of posts between ({:s}) and ({:s}) was queued. {:s}'.format(str(bdt), str(edt), success if result else failure)
                # Command: report posts from yesterday
                elif scmd == 'yesterday':
                    # Grab the current time and move to the beginning of the day
                    dtm = datetime.datetime.now().replace(hour=0,minute=0,second=0,microsecond=0)
                    # Subtract one second to obtain the end of yesterday
                    edt = dtm - datetime.timedelta(seconds=1)
                    # Subtract one day to obtain the beginning of yesterday
                    bdt = dtm - datetime.timedelta(days=1)
                    # Attempt to generate a report of the user posts
                    result = ReportUserPostsBetween(userid, bdt, edt)
                    # Now generate the reply message
                    reply = 'Report of posts between ({:s}) and ({:s}) was queued. {:s}'.format(str(bdt), str(edt), success if result else failure)
                # Command: report posts from a certain amount of hours
                elif scmd == 'hour':
                    # The default number of hours to go back
                    hsub = 1
                    # Was there a specific number of hours to go back?
                    if sections > 3:
                        try:
                            hsub = max(min(23, int(cmd_list[3])), 1)
                        except:
                            pass
                    # Grab the current time
                    edt = datetime.datetime.now().replace(microsecond=0)
                    # Subtract the specified hours from the current time
                    bdt = edt - datetime.timedelta(hours=int(hsub))
                    # Attempt to generate a report of the user posts
                    result = ReportUserPostsBetween(userid, bdt, edt)
                    # Now generate the reply message
                    reply = 'Report of posts between ({:s}) and ({:s}) was queued. {:s}'.format(str(bdt), str(edt), success if result else failure)
                else:
                    # Generate the reply message to warn the user
                    reply = 'Unknown Range: {:s}'.format(scmd)
                # Now that we're here, let's execute the resulted query
                try:
                    cursor.execute("UPDATE mybb_dvz_shoutbox SET text = '{:s}', modified = {:d} WHERE id = {:d}".format(reply, int(time.time()), shoutid))
                except Exception as e:
                    # Display information
                    print('Failed to generate report: {:s}'.format(str(e)))
                    # Ignore the error
                    pass
                # Move to the next command
                continue
            # Command: run preliminary post count for owners and warn them to complete their posts
            elif cmd == 'alert':
                # Default to an empty command
                scmd = ''
                # Default to a non silent report
                silent = False
                # Is there a second parameter?
                if sections > 2:
                    # Should we run a silent report of current month?
                    if cmd_list[2] == 'silent':
                        # Enable silent report
                        silent = True
                        # Default to current month
                        scmd = 'current'
                    # Just save whatever parameter that was
                    else:
                        scmd = cmd_list[2]
                    # Is there a third parameter?
                    if sections > 3:
                        # Should we run a silent report of current month?
                        if cmd_list[3] == 'silent':
                            silent = True
                        # Just save whatever parameter that was if still none
                        elif not scmd:
                            scmd = cmd_list[3]
                # Default to current month
                else:
                    scmd = 'current'
                # The success and failure messages to keep code shorter
                success, failure = 'Please check your private messages', 'However, an error occurred'
                # The reply message to replace the command shout
                reply = None
                # See if the user even has the privilege to use this command
                if userid not in g_ManagerID:
                    # Generate the warning message
                    reply = 'You do not have the privilege to perform such action'
                # Command: report posts from current month
                elif scmd == 'current':
                    # Grab the current time and move to the 25'th day then add 10 days to jump to the next month
                    edt = datetime.datetime.now().replace(day=25) + datetime.timedelta(days=10)
                    # Now go back to the first day and subtract one second so we have the end of current month
                    edt = edt.replace(day=1,hour=0,minute=0,second=0,microsecond=0) - datetime.timedelta(seconds=1)
                    # Grab the current time and move to the beginning of the month
                    bdt = datetime.datetime.now().replace(day=1,hour=0,minute=0,second=0,microsecond=0)
                    # Attempt to generate a report of the user posts
                    try:
                        result = AdminReport(userid, bdt, edt, silent, False)
                    except Exception as e:
                        # Display information
                        print('Failed generate alert: {:s}'.format(str(e)))
                        # Specify we failed
                        result = False
                        # Ignore error
                        pass
                    # Now generate the reply message
                    reply = '{:s} of posts between ({:s}) and ({:s}) was queued. {:s}'.format('Alert' if not silent else 'Silent alert', str(bdt), str(edt), success if result else failure)
                # Command: report posts from previous month
                elif scmd == 'previous':
                    # Subtract 1 second from the beginning of the current month so we jump to the end of previous month
                    edt = datetime.datetime.now().replace(day=1,hour=0,minute=0,second=0,microsecond=0) - datetime.timedelta(seconds=1)
                    # Now use that to obtain the beginning of the previous month as well
                    bdt = edt.replace(day=1,hour=0,minute=0,second=0)
                    # Attempt to generate a report of the user posts
                    try:
                        result = AdminReport(userid, bdt, edt, silent, False)
                    except Exception as e:
                        # Display information
                        print('Failed generate alert: {:s}'.format(str(e)))
                        # Specify we failed
                        result = False
                        # Ignore error
                        pass
                    # Now generate the reply message
                    reply = '{:s} of posts between ({:s}) and ({:s}) was queued. {:s}'.format('Alert' if not silent else 'Silent alert', str(bdt), str(edt), success if result else failure)
                else:
                    # Generate the reply message to warn the user
                    reply = 'Unknown Range: {0}'.format(scmd)
                # Now that we're here, let's execute the resulted query
                try:
                    cursor.execute("UPDATE mybb_dvz_shoutbox SET text = '{:s}', modified = {:d} WHERE id = {:d}".format(reply, int(time.time()), shoutid))
                except Exception as e:
                    # Display information
                    print('Failed to generate alert: {:s}'.format(str(e)))
                # Move to the next command
                continue
            # Command: final post count for owners where they're warned that they haven't completed their posts
            elif cmd == 'warn':
                # Default to an empty command
                scmd = ''
                # Default to a non silent report
                silent = False
                # Is there a second parameter?
                if sections > 2:
                    # Should we run a silent report of current month?
                    if cmd_list[2] == 'silent':
                        # Enable silent report
                        silent = True
                        # Default to current month
                        scmd = 'current'
                    # Just save whatever parameter that was
                    else:
                        scmd = cmd_list[2]
                    # Is there a third parameter?
                    if sections > 3:
                        # Should we run a silent report of current month?
                        if cmd_list[3] == 'silent':
                            silent = True
                        # Just save whatever parameter that was if still none
                        elif not scmd:
                            scmd = cmd_list[3]
                # Default to current month
                else:
                    scmd = 'current'
                # The success and failure messages to keep code shorter
                success, failure = 'Please check your private messages', 'However, an error occurred'
                # The reply message to replace the command shout
                reply = None
                # See if the user even has the privilege to use this command
                if userid not in g_ManagerID:
                    # Generate the warning message
                    reply = 'You do not have the privilege to perform such action'
                # Command: report posts from current month
                elif scmd == 'current':
                    # Grab the current time and move to the 25'th day then add 10 days to jump to the next month
                    edt = datetime.datetime.now().replace(day=25) + datetime.timedelta(days=10)
                    # Now go back to the first day and subtract one second so we have the end of current month
                    edt = edt.replace(day=1,hour=0,minute=0,second=0,microsecond=0) - datetime.timedelta(seconds=1)
                    # Grab the current time and move to the beginning of the month
                    bdt = datetime.datetime.now().replace(day=1,hour=0,minute=0,second=0,microsecond=0)
                    # Attempt to generate a report of the user posts
                    try:
                        result = AdminReport(userid, bdt, edt, silent, True)
                    except Exception as e:
                        # Display information
                        print('Failed generate warning: {:s}'.format(str(e)))
                        # Specify we failed
                        result = False
                        # Ignore error
                        pass
                    # Now generate the reply message
                    reply = '{:s} of posts between ({:s}) and ({:s}) was queued. {:s}'.format('Warning' if not silent else 'Silent warning', str(bdt), str(edt), success if result else failure)
                # Command: report posts from previous month
                elif scmd == 'previous':
                    # Subtract 1 second from the beginning of the current month so we jump to the end of previous month
                    edt = datetime.datetime.now().replace(day=1,hour=0,minute=0,second=0,microsecond=0) - datetime.timedelta(seconds=1)
                    # Now use that to obtain the beginning of the previous month as well
                    bdt = edt.replace(day=1,hour=0,minute=0,second=0)
                    # Attempt to generate a report of the user posts
                    try:
                        result = AdminReport(userid, bdt, edt, silent, True)
                    except Exception as e:
                        # Display information
                        print('Failed generate warning: {:s}'.format(str(e)))
                        # Specify we failed
                        result = False
                        # Ignore error
                        pass
                    # Now generate the reply message
                    reply = '{:s} of posts between ({:s}) and ({:s}) was queued. {:s}'.format('Warning' if not silent else 'Silent warning', str(bdt), str(edt), success if result else failure)
                else:
                    # Generate the reply message to warn the user
                    reply = 'Unknown Range: {:s}'.format(scmd)
                # Now that we're here, let's execute the resulted query
                try:
                    cursor.execute("UPDATE mybb_dvz_shoutbox SET text = '{:s}', modified = {:d} WHERE id = {:d}".format(reply, int(time.time()), shoutid))
                except Exception as e:
                    # Display information
                    print('Failed to generate alert: {:s}'.format(str(e)))
                # Move to the next command
                continue
            # Command: exit the main program loop
            elif cmd == 'stop':
                # The reply message to replace the command shout
                reply = None
                # Is this user the bot manager?
                if userid in g_ManagerID:
                    # Generate the reply message
                    reply = 'Post counter scheduled stop.'
                    # Make this the last pulse
                    g_RunLoop = False
                else:
                    # Generate the reply message
                    reply = 'You do not have the privilege to perform such action'
                # Update the shout message to display the result
                try:
                    cursor.execute("UPDATE mybb_dvz_shoutbox SET text = '{:s}', modified = {:d} WHERE id = {:d}".format(reply, int(time.time()), shoutid))
                except Exception as e:
                    # Display information
                    print('Failed to stop bot: {:s}'.format(str(e)))
                # Move to the next command
                continue
            # Command: reload the user/owner list
            elif cmd == 'update':
                # The reply message to replace the command shout
                reply = None
                # Is this user the bot manager?
                if userid in g_ManagerID:
                    # Perform the update and generate the reply message
                    reply = 'Owner list update scheduled' if UpdateOwnersList() else 'Owner list update scheduled but failed to complete'
                else:
                    # Generate the reply message
                    reply = 'You do not have the privilege to perform such action'
                # Update the shout message to display the result
                try:
                    cursor.execute("UPDATE mybb_dvz_shoutbox SET text = '{:s}', modified = {:d} WHERE id = {:d}".format(reply, int(time.time()), shoutid))
                except Exception as e:
                    # Display information
                    print('Failed to update owners list: {:s}'.format(str(e)))
                # Move to the next command
                continue
            # Command: time left until the month ends
            elif cmd == 'left':
                # Grab the beginning of the current month and 32 days so that leap into the next one 
                dtm = datetime.datetime.now().replace(day=1, hour=0, minute=0, second=0) + datetime.timedelta(days=32)
                # Now go back to the beginning of the next month and and subtract the current time from it
                remaining = dtm.replace(day=1) - datetime.datetime.now()
                # Update the shout message to display the information
                try:
                    cursor.execute("UPDATE mybb_dvz_shoutbox SET text = 'Time left: {:s}', modified = {:d} WHERE id = {:d}".format(str(remaining), int(time.time()), shoutid))
                except Exception as e:
                    # Display information
                    print('Failed to count left time: {:s}'.format(str(e)))
                # Move to the next command
                continue
            # Command: time elapsed from the beginning of the month
            elif cmd == 'elapsed':
                # Find the the time elapsed since the beginning of the month
                time_elapsed = datetime.datetime.now() - datetime.datetime.now().replace(day=1, hour=0, minute=0, second=0)
                # Update the shout message to display the information
                try:
                    cursor.execute("UPDATE mybb_dvz_shoutbox SET text = 'Time elapsed: {:s}', modified = {:d} WHERE id = {:d}".format(str(time_elapsed), int(time.time()), shoutid))
                except Exception as e:
                    # Display information
                    print('Failed to count elapsed time: {:s}'.format(str(e)))
                # Move to the next command
                continue
            # We don't recognize such command
            else:
                # Update the shout message to display the warning
                cursor.execute("UPDATE mybb_dvz_shoutbox SET text = 'Unknown Command: {:s}', modified = {:d} WHERE id = {:d}".format(cmd, int(time.time()), shoutid))
    # Commit database changes, if any
    g_Db.commit()

# -----------------------------------------------------------------------------------------------
# Main loop.
while g_RunLoop:
    try:
        ProgramPulse()
    except Exception as e:
        print('Pulse failure: {:s}'.format(str(e)))
        pass

# -----------------------------------------------------------------------------------------------
# Disconnect from server.
g_Db.close()
